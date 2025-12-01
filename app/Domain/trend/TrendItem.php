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
    ) {}

    /**
     * RSS item 데이터로부터 TrendItem 생성
     */
    public static function fromRssItem(\SimpleXMLElement $item): self
    {
        // pubDate 파싱
        $pubDateString = (string) $item->pubDate;
        $pubDate = new \DateTimeImmutable($pubDateString);

        // 이미지 URL 추출 (description에서)
        $description = (string) $item->description;
        $imageUrl = self::extractImageUrl($description);

        // 트래픽 카운트 추출 (ht:approx_traffic)
        $trafficCount = null;
        if (isset($item->children('ht', true)->approx_traffic)) {
            $trafficCount = (int) $item->children('ht', true)->approx_traffic;
        }

        return new self(
            title: (string) $item->title,
            description: strip_tags($description),
            link: (string) $item->link,
            pubDate: $pubDate,
            imageUrl: $imageUrl,
            trafficCount: $trafficCount,
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
        ];
    }
}
