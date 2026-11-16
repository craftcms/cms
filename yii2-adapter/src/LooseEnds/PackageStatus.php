<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use function CraftCms\Cms\t;

/**
 * Whether a plugin or package that needs the adapter has a release that doesn’t, per Craftnet.
 *
 * @internal
 */
enum PackageStatus: string
{
    case UpdateAvailable = 'update-available';
    case NoRelease = 'no-release';
    case Abandoned = 'abandoned';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::UpdateAvailable => t('Update available', category: 'yii2-adapter'),
            self::NoRelease => t('Waiting on developer', category: 'yii2-adapter'),
            self::Abandoned => t('Abandoned', category: 'yii2-adapter'),
            self::Unknown => t('No update info', category: 'yii2-adapter'),
        };
    }

    /**
     * Returns the `craft-badge` fill for the status.
     */
    public function fill(): string
    {
        return match ($this) {
            self::UpdateAvailable => 'green',
            self::NoRelease => 'amber',
            self::Abandoned => 'red',
            self::Unknown => 'gray',
        };
    }
}
