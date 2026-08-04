<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

enum ImageTransformQuality: int
{
    use CanSelect;

    case Low = 10;
    case Medium = 30;
    case High = 60;
    case VeryHigh = 80;
    case Maximum = 100;

    public function label(): string
    {
        return match ($this) {
            self::Low => t('Low'),
            self::Medium => t('Medium'),
            self::High => t('High'),
            self::VeryHigh => t('Very High'),
            self::Maximum => t('Maximum'),
        };
    }
}
