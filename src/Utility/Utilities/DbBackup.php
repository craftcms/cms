<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Cp\VueComponent;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * DbBackup represents a DbBackup dashboard widget.
 */
final class DbBackup extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Database Backup');
    }

    #[\Override]
    public static function id(): string
    {
        return 'db-backup';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'database';
    }

    #[\Override]
    public static function contentHtml(): string
    {
        return VueComponent::render('DatabaseBackup');
    }
}
