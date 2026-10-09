<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

enum ImageTransformInterlace: string
{
    use CanSelect;

    case None = 'none';
    case Line = 'line';
    case Plane = 'plane';
    case Partition = 'partition';

    public function label(): string
    {
        return match ($this) {
            self::None => t('None'),
            self::Line => t('Line'),
            self::Plane => t('Plane'),
            self::Partition => t('Partition'),
        };
    }
}
