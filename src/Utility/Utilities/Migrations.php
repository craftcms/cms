<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

/**
 * Migrations represents a Migrations utility.
 */
class Migrations extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Migrations');
    }

    #[Override]
    public static function id(): string
    {
        return 'migrations';
    }

    #[Override]
    public static function icon(): string
    {
        return 'up';
    }

    #[Override]
    public static function badgeCount(): int
    {
        return count(app(Migrator::class)
            ->track('content')
            ->getPendingMigrations());
    }

    #[Override]
    public static function contentHtml(): string
    {
        $migrator = app(Migrator::class)->track('content');

        $migrationHistory = $migrator->getRepository()->getMigrations(1_000);
        $newMigrations = array_map(
            fn (string $migration) => Str::after($migration, database_path('migrations/')),
            $migrator->getPendingMigrations(),
        );

        return Html::tag('Migrations', attributes: [
            ':migrationHistory' => $migrationHistory,
            ':newMigrations' => $newMigrations,
        ]);
    }
}
