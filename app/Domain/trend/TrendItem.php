<?php

namespace App\Domain\trend;

class TrendItem
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $link,
        public readonly \DateTimeImmutable $pubDate,
        public readonly ?string $imageUrl = null,
        public readonly ?int $trafficCount = null,
        public readonly ?string $picture = null,
        public readonly ?string $pictureSource = null,
        public readonly array $newsItems = [],
    ) {}

    /**
     * RSS item 데이터로부터 TrendItem 생성
     */
    public static function fromRssItem(\SimpleXMLElement $item): self
    {
        // pubDate 파싱
        $pubDateString = (string) $item->pubDate;
        $pubDate = new \DateTimeImmutable($pubDateString);

        // namespace 설정
        $ht = $item->children('ht', true);

        // 이미지 URL 추출 (description에서)
        $description = (string) $item->description;
        $imageUrl = self::extractImageUrl($description);

        // 트래픽 카운트 추출 (ht:approx_traffic)
        $trafficCount = null;
        if (isset($ht->approx_traffic)) {
            $trafficString = (string) $ht->approx_traffic;
            // "100+" 형식에서 숫자만 추출
            $trafficCount = (int) preg_replace('/[^0-9]/', '', $trafficString);
        }

        // ht:picture 추출
        $picture = isset($ht->picture) ? (string) $ht->picture : null;

        // ht:picture_source 추출
        $pictureSource = isset($ht->picture_source) ? (string) $ht->picture_source : null;

        // ht:news_item 추출
        $newsItems = [];
        if (isset($ht->news_item)) {
            foreach ($ht->news_item as $newsItem) {
                $newsItems[] = [
                    'title' => (string) $newsItem->news_item_title,
                    'snippet' => (string) $newsItem->news_item_snippet,
                    'url' => (string) $newsItem->news_item_url,
                    'picture' => (string) $newsItem->news_item_picture,
                    'source' => (string) $newsItem->news_item_source,
                ];
            }
        }

        return new self(
            title: (string) $item->title,
            description: strip_tags($description),
            link: (string) $item->link,
            pubDate: $pubDate,
            imageUrl: $imageUrl,
            trafficCount: $trafficCount,
            picture: $picture,
            pictureSource: $pictureSource,
            newsItems: $newsItems,
        );
    }

    /**
     * HTML description에서 이미지 URL 추출
     */
    private static function extractImageUrl(string $html): ?string
    {
        if (preg_match('/<img[^>]+src="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'pub_date' => $this->pubDate->format('Y-m-d H:i:s'),
            'image_url' => $this->imageUrl,
            'traffic_count' => $this->trafficCount,
            'picture' => $this->picture,
            'picture_source' => $this->pictureSource,
            'news_items' => $this->newsItems,
        ];
    }
}
