<?php

namespace App\Enums;

enum ContentsType: string
{
    case BULLETIN = 'bulletin';
    case NEWS = 'news';
    case NATE_NEWS = 'nate_news';
    case YOUTUBE = 'youtube';
    case HTML = 'html';
}
