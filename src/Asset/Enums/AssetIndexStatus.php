<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

enum AssetIndexStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Indexed = 'indexed';
    case Skipped = 'skipped';
    case Missing = 'missing';
    case Failed = 'failed';

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => $status === self::Processing,
            self::Processing => in_array($status, [self::Indexed, self::Skipped, self::Missing, self::Failed], true),
            self::Missing, self::Failed => $status === self::Pending,
            self::Indexed, self::Skipped => false,
        };
    }
}
