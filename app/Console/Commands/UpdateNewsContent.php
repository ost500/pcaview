<?php

namespace App\Console\Commands;

use App\Models\Contents;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateNewsContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:update {id? : Content ID to update} {--all : Update all news contents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update news content title and body from file_url';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->updateAllNews();
        } elseif ($this->argument('id')) {
            $this->updateSingleNews($this->argument('id'));
        } else {
            $this->error('Please provide a content ID or use --all option');
            return 1;
        }

        return 0;
    }

    protected function updateSingleNews($id)
    {
        $content = Contents::find($id);

        if (!$content) {
            $this->error("Content ID {$id} not found");
            return;
        }

        if (!in_array($content->type, ['news', 'nate_news'])) {
            $this->error("Content ID {$id} is not a news type");
            return;
        }

        if (!$content->file_url) {
            $this->error("Content ID {$id} has no file_url");
            return;
        }

        $this->info("Updating content ID: {$id}");
        $this->updateContentBody($content);
    }

    protected function updateAllNews()
    {
        $contents = Contents::whereIn('type', ['news', 'nate_news'])
            ->whereNotNull('file_url')
            ->where('file_url', '!=', '')
            ->get();

        $this->info("Found {$contents->count()} news contents to update");

        $progressBar = $this->output->createProgressBar($contents->count());
        $progressBar->start();

        foreach ($contents as $content) {
            $this->updateContentBody($content);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Update completed!');
    }

    protected function updateContentBody(Contents $content)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'ko-KR,ko;q=0.9',
                    'Accept-Charset' => 'UTF-8',
                ])
                ->get($content->file_url);

            if (!$response->successful()) {
                $this->warn("Failed to fetch URL for content ID {$content->id}");
                return;
            }

            $html = $response->body();

            // 인코딩 감지 및 UTF-8 변환
            $encoding = mb_detect_encoding($html, ['UTF-8', 'EUC-KR', 'CP949', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $html = mb_convert_encoding($html, 'UTF-8', $encoding);
            }

            $extracted = $this->extractTitleAndBodyFromHtml($html, $content->file_url);

            $updated = false;
            $updateData = [];

            if ($extracted['title']) {
                $updateData['title'] = $extracted['title'];
                $updated = true;
            }

            if ($extracted['body']) {
                $updateData['body'] = $extracted['body'];
                $updated = true;
            }

            if ($updated) {
                $content->update($updateData);
                $titleInfo = isset($updateData['title']) ? " (title updated)" : "";
                $this->line("✓ Updated content ID: {$content->id}{$titleInfo}");
            } else {
                $this->warn("No content extracted for ID {$content->id}");
            }

        } catch (\Exception $e) {
            $this->error("Error updating content ID {$content->id}: {$e->getMessage()}");
        }
    }

    protected function extractTitleAndBodyFromHtml(string $html, string $url): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 제목 추출
        $title = null;
        $titleSelectors = [
            "//h1[@id='articleTitle']",        // 네이트 뉴스 제목
            "//h1[@class='articleTitle']",
            "//h2[@id='articleTitle']",
            "//h2[@class='articleTitle']",
            "//meta[@property='og:title']/@content",  // OG 태그
            "//meta[@name='title']/@content",
            "//h1[contains(@class, 'article-title')]",
            "//h1[contains(@class, 'news-title')]",
            "//h1",                            // 일반 h1
            "//title",                         // 페이지 타이틀
        ];

        foreach ($titleSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $titleNode = $nodes->item(0);
                if ($titleNode) {
                    $title = trim($titleNode->nodeValue ?? $titleNode->textContent);
                    if (!empty($title)) {
                        // 사이트명 및 카테고리 제거
                        // "뉴스 제목 - 네이트뉴스" -> "뉴스 제목"
                        // ":네이트 연예" -> ""
                        // "네이트 뉴스 - 제목" -> "제목"
                        $title = preg_replace('/^[:\s]*(네이트|NATE)\s*(뉴스|연예|스포츠|경제|사회|정치|IT|News)[:\s]*/ui', '', $title);
                        $title = preg_replace('/\s*[-|:]\s*(네이트|NATE)\s*(뉴스|연예|스포츠|경제|사회|정치|IT|News).*$/ui', '', $title);
                        $title = trim($title);
                        break;
                    }
                }
            }
        }

        // 본문 추출
        $bodyHtml = null;
        $bodySelectors = [
            // 네이트 뉴스 전용
            "//div[@id='articleContents']",   // 네이트 뉴스 기사 내용 (최우선)
            "//div[@id='realArtcContents']",  // 네이트 뉴스 실제 기사 내용
            "//div[@id='articleBody']",
            "//div[@id='newsBody']",
            "//div[@class='articleBody']",
            // 일반적인 article 태그
            "//article",
            "//div[contains(@class, 'article-body')]",
            "//div[contains(@class, 'article-content')]",
            "//div[contains(@class, 'news-body')]",
            "//div[contains(@class, 'content-body')]",
            "//div[contains(@class, 'post-content')]",
            "//div[contains(@class, 'entry-content')]",
            "//div[@id='article-body']",
            "//div[@id='article-content']",
            "//div[@id='news-content']",
            "//main",
        ];

        foreach ($bodySelectors as $selector) {
            $nodes = $xpath->query($selector);

            if ($nodes && $nodes->length > 0) {
                $node = $nodes->item(0);

                // C14N으로 저장하여 HTML 엔티티 인코딩 방지
                $bodyHtml = $node->C14N();

                // 광고, 스크립트 등 불필요한 요소 제거
                $bodyHtml = $this->cleanHtml($bodyHtml);

                if (strlen(strip_tags($bodyHtml)) > 100) {
                    break;
                }
                $bodyHtml = null;
            }
        }

        return [
            'title' => $title,
            'body' => $bodyHtml,
        ];
    }

    protected function cleanHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 제거할 요소들
        $removeSelectors = [
            "//script",
            "//style",
            "//iframe",
            "//noscript",
            "//ins",
            "//*[contains(@class, 'ad')]",
            "//*[contains(@id, 'ad')]",
            "//*[contains(@class, 'adsbygoogle')]",
            "//*[@data-ad-slot]",
        ];

        foreach ($removeSelectors as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        // C14N으로 저장하여 HTML 엔티티 인코딩 방지
        $html = $dom->documentElement ? $dom->documentElement->C14N() : '';

        // 구글 광고 주석 제거
        $html = preg_replace('/<!--.*?google_ad.*?-->/s', '', $html);

        // HTML 주석 제거
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return trim($html);
    }
}
