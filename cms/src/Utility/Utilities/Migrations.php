<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use CraftCms\Cms\Utility\Utility;

/**
 * Migrations represents a Migrations utility.
 *
 * @since 3.0.0
 */
final readonly class Migrations extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Migrations');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'migrations';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'up';
    }

    /**
     * {@inheritdoc}
     */
    public static function badgeCount(): int
    {
        return count(Craft::$app->getContentMigrator()->getNewMigrations());
    }

    /**
     * {@inheritdoc}
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
