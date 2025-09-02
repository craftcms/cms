<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utility;

/**
 * Migrations represents a Migrations utility.
 *
 * @since 6.0.0
 */
final class Migrations extends Utility
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
        return count(app(Migrator::class)
            ->track('content')
            ->getPendingMigrations());
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $migrator = app(Migrator::class)->track('content');

        $migrationHistory = $migrator->getRepository()->getMigrations(1000);
        $newMigrations = array_map(
            fn (string $migration) => Str::after($migration, database_path('migrations/')),
            $migrator->getPendingMigrations(),
        );

        return $view->renderTemplate('_components/utilities/Migrations.twig', [
            'migrationHistory' => $migrationHistory,
            'newMigrations' => $newMigrations,
        ]);
    }
}
