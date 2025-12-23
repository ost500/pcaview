<?php

namespace App\Domain\church\msch;

enum MSCHContentsType: string
{
    case BULLETIN = 'bulletin';
    case NEWS = 'news';
    case NATE_NEWS = 'nate_news';
    case VIDEO = 'video';
    case HTML = 'html';
}
