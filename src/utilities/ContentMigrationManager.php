<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\db\MigrationManager;
use craft\base\Utility;
use yii\base\Exception;

/**
 * ContentMigrationManager represents a ContentMigrationManager dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ContentMigrationManager extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Content Migration Manager');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'content-migration-manager';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@app/icons/newspaper.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $migrator = Craft::$app->getContentMigrator();

        $migrationHistory = $migrator->getMigrationHistory();
        $newMigrations = $migrator->getNewMigrations();

        return $view->renderTemplate('_components/utilities/ContentMigrationManager', [
            'migrationHistory' => $migrationHistory,
            'newMigrations' => $newMigrations
        ]);
    }

}
