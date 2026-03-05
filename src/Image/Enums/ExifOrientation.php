<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

enum ExifOrientation: int
{
    case Rotate0 = 1;
    case Rotate0Mirrored = 2;
    case Rotate180 = 3;
    case Rotate180Mirrored = 4;
    case Rotate90Mirrored = 5;
    case Rotate90 = 6;
    case Rotate270Mirrored = 7;
    case Rotate270 = 8;
}
