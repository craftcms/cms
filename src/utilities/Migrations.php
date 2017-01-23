<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use yii\base\Exception;

/**
 * Migrations represents a Migrations utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Migrations extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Migrations');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'migrations';
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

        return $view->renderTemplate('_components/utilities/Migrations', [
            'migrationHistory' => $migrationHistory,
            'newMigrations' => $newMigrations
        ]);
    }

}
