<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

final class DbBackup extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Database Backup');
    }

    #[Override]
    public static function id(): string
    {
        return 'db-backup';
    }

    #[Override]
    public static function icon(): string
    {
        return 'database';
    }

    #[Override]
    public static function contentHtml(): string
    {
        return Html::tag('DatabaseBackup');
    }
}
