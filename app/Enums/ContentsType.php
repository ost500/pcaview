<?php

namespace App\Enums;

enum ContentsType: string
{
    case BULLETIN = 'bulletin';
    case NEWS = 'news';
    case NATE_NEWS = 'nate_news';
    case NAVER_NEWS = 'naver_news';
    case YOUTUBE = 'youtube';
    case HTML = 'html';

    /**
     * 뉴스 타입인지 확인
     * 저작권 보호를 위해 뉴스 관련 타입들을 필터링
     *
     * @return bool
     */
    public function isNewsType(): bool
    {
        return in_array($this, [
            self::NEWS,
            self::NATE_NEWS,
            self::NAVER_NEWS,
        ]);
    }

    /**
     * 주어진 문자열이 뉴스 타입인지 확인
     *
     * @param  string  $type
     * @return bool
     */
    public static function isNews(string $type): bool
    {
        return in_array($type, [
            self::NEWS->value,
            self::NATE_NEWS->value,
            self::NAVER_NEWS->value,
        ]);
    }
}
