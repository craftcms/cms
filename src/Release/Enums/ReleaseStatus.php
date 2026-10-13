<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Enums;

use CraftCms\Cms\Shared\Enums\Color;

use function CraftCms\Cms\t;

enum ReleaseStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => t('Draft'),
            self::Scheduled => t('Scheduled'),
            self::Publishing => t('Publishing'),
            self::Published => t('Published'),
            self::Failed => t('Failed'),
        };
    }

    public function color(): Color
    {
        return match ($this) {
            self::Draft => Color::Gray,
            self::Scheduled, self::Publishing => Color::Sky,
            self::Published => Color::Emerald,
            self::Failed => Color::Red,
        };
    }

    public function isMutable(): bool
    {
        return in_array($this, [self::Draft, self::Failed], true);
    }
}
