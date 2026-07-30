<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;
use CraftCms\Cms\Cp\Contracts\SelectableEnumInterface;

use function CraftCms\Cms\t;

enum ImageTransformPosition: string implements SelectableEnumInterface
{
    use CanSelect;

    case TopLeft = 'top-left';
    case TopCenter = 'top-center';
    case TopRight = 'top-right';
    case CenterLeft = 'center-left';
    case CenterCenter = 'center-center';
    case CenterRight = 'center-right';
    case BottomLeft = 'bottom-left';
    case BottomCenter = 'bottom-center';
    case BottomRight = 'bottom-right';

    public function label(): string
    {
        return match ($this) {
            self::TopLeft => t('Top-Left'),
            self::TopCenter => t('Top-Center'),
            self::TopRight => t('Top-Right'),
            self::CenterLeft => t('Center-Left'),
            self::CenterCenter => t('Center-Center'),
            self::CenterRight => t('Center-Right'),
            self::BottomLeft => t('Bottom-Left'),
            self::BottomCenter => t('Bottom-Center'),
            self::BottomRight => t('Bottom-Right'),
        };
    }
}
