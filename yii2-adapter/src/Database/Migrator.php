<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Database;

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Migrator as CoreMigrator;
use Override;
use ReflectionClass;

class Migrator extends CoreMigrator
{
    #[Override]
    protected function resolvePath(string $path): object
    {
        $migrationName = $this->getMigrationName($path);
        $realPath = realpath($path);

        foreach (array_reverse(get_declared_classes()) as $class) {
            if ($class !== $migrationName && !str_ends_with($class, "\\$migrationName")) {
                continue;
            }

            if ($realPath !== new ReflectionClass($class)->getFileName()) {
                continue;
            }

            return is_a($class, Migration::class, true)
                ? app()->make($class)
                : new MigrationWrapper($class);
        }

        return parent::resolvePath($path);
    }
}
