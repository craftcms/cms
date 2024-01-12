<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;

/**
 * Migrations represents a Migrations utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Migrations extends Utility
{
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
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@appicons/arrow-up.svg');
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return count(Craft::$app->getContentMigrator()->getNewMigrations());
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

        return $view->renderTemplate('_components/utilities/Migrations.twig', [
            'migrationHistory' => $migrationHistory,
            'newMigrations' => $newMigrations,
        ]);
    }
}
