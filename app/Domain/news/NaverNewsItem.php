<?php

namespace App\Domain\news;

/**
 * Naver 뉴스 아이템 데이터 클래스
 */
class NaverNewsItem
{
    public function __construct(
        public readonly string $title,
        public readonly string $snippet,
        public readonly string $url,
        public readonly string $source,
        public readonly ?string $picture,
        public readonly ?string $publishedAt,
    ) {}

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'snippet' => $this->snippet,
            'url' => $this->url,
            'source' => $this->source,
            'picture' => $this->picture,
            'published_at' => $this->publishedAt,
        ];
    }

    /**
     * JSON으로 변환
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
