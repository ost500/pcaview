<?php

namespace App\Domain\contents;

enum ContentsFileType
{
    case LOCAL_IMAGE;
    case REMOTE_IMAGE;
    case YOUTUBE;
}
