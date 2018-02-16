<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\dbbackup\DbBackupAsset;

/**
 * DbBackup represents a DbBackup dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DbBackup extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Database Backup');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'db-backup';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/database.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DbBackupAsset::class);
        $view->registerJs('new Craft.DbBackupUtility(\'db-backup\');');

        return $view->renderTemplate('_components/utilities/DbBackup');
    }
}
