<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

enum ImageDriver: string
{
    case Gd = 'gd';
    case Imagick = 'imagick';
    case Vips = 'vips';
}
