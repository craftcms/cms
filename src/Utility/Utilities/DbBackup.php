<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\dbbackup\DbBackupAsset;
use CraftCms\Cms\Utility\Utility;

/**
 * DbBackup represents a DbBackup dashboard widget.
 */
final class DbBackup extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return Craft::t('app', 'Database Backup');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'db-backup';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DbBackupAsset::class);
        $view->registerJs('new Craft.DbBackupUtility(\'db-backup\');');

        return $view->renderTemplate('_components/utilities/DbBackup.twig');
    }
}
