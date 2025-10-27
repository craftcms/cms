<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Migrations represents a Migrations utility.
 */
final class Migrations extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Migrations');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'migrations';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'up';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function badgeCount(): int
    {
        return count(app(Migrator::class)
            ->track('content')
            ->getPendingMigrations());
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $migrator = app(Migrator::class)->track('content');

        $migrationHistory = $migrator->getRepository()->getMigrations(1_000);
        $newMigrations = array_map(
            fn (string $migration) => Str::after($migration, database_path('migrations/')),
            $migrator->getPendingMigrations(),
        );

        return Craft::$app->getView()->renderTemplate('_components/utilities/Migrations.twig', [
            'migrationHistory' => $migrationHistory,
            'newMigrations' => $newMigrations,
        ]);
    }
}
