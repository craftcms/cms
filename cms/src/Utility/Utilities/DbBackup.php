<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\dbbackup\DbBackupAsset;
use CraftCms\Cms\Utility\Utility;

/**
 * DbBackup represents a DbBackup dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class DbBackup extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Database Backup');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'db-backup';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): ?string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DbBackupAsset::class);
        $view->registerJs('new Craft.DbBackupUtility(\'db-backup\');');

        return $view->renderTemplate('_components/utilities/DbBackup.twig');
    }
}
