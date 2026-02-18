<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\dbbackup\DbBackupAsset;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * DbBackup represents a DbBackup dashboard widget.
 */
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
        Craft::$app->getView()->registerAssetBundle(DbBackupAsset::class);

        AssetRegistry::js('new Craft.DbBackupUtility(\'db-backup\');');

        return template('_components/utilities/DbBackup');
    }
}
