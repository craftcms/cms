<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

enum SectionType: string
{
    use CanSelect;

    case Single = 'single';
    case Channel = 'channel';
    case Structure = 'structure';

    public function label(): string
    {
        return match ($this) {
            self::Single => t('Single'),
            self::Channel => t('Channel'),
            self::Structure => t('Structure'),
        };
    }

    public function descriptiveLabel(): string
    {
        return match ($this) {
            self::Single => t('Single: for one-off content'),
            self::Channel => t('Channel: for repeating content'),
            self::Structure => t('Structure: for ordered/hierarchical content'),
        };
    }
}
