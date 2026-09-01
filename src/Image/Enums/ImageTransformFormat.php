<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

enum ImageTransformFormat: string
{
    use CanSelect;

    case JPG = 'jpg';
    case GIF = 'gif';
    case PNG = 'png';
    case WEBP = 'webp';
    case AVIF = 'avif';
    case BMP = 'bmp';
    case HEIC = 'heic';
    case ICO = 'ico';
    case JP2 = 'jp2';
    case JXL = 'jxl';
    case TIFF = 'tiff';

    public function label(): string
    {
        return $this->name;
    }
}
