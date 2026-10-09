<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

enum ImageTransformMode: string
{
    use CanSelect;

    case Crop = 'crop';
    case Fit = 'fit';
    case Stretch = 'stretch';
    case Letterbox = 'letterbox';

    public function label(): string
    {
        return match ($this) {
            self::Crop => t('Crop'),
            self::Fit => t('Fit'),
            self::Stretch => t('Stretch'),
            self::Letterbox => t('Letterbox'),
        };
    }
}
