<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\Tests;

use CraftCms\Cms\Database\Migrator;

class FakeMigrator extends Migrator
{
    public array $loggedMigrations = [];

    public array $pendingMigrations = [];

    public array $resetArguments = [];

    public ?string $tracked = null;

    public array $configuredPaths = [];

    public mixed $runMigrationArgument = null;

    public ?string $runMigrationMethod = null;

    public function __construct() {}

    #[\Override]
    public function track(string $track): self
    {
        $this->tracked = $track;

        return $this;
    }

    #[\Override]
    public function setPaths(array $paths): self
    {
        $this->configuredPaths = $paths;
        $this->paths = $paths;

        return $this;
    }

    #[\Override]
    public function runMigration($migration, $method): void
    {
        $this->runMigrationArgument = $migration;
        $this->runMigrationMethod = $method;
    }

    #[\Override]
    public function getRepository(): object
    {
        return new readonly class($this)
        {
            public function __construct(private FakeMigrator $migrator) {}

            public function log(string $migration, int $batch): void
            {
                $this->migrator->loggedMigrations[] = [$migration, $batch];
            }
        };
    }

    #[\Override]
    public function getPendingMigrations($paths = []): array
    {
        return $this->pendingMigrations;
    }

    #[\Override]
    public function getMigrationName($path): string
    {
        return pathinfo((string) $path, PATHINFO_FILENAME);
    }

    #[\Override]
    public function resetMigrations(array $migrations, array $paths, $pretend = false): array
    {
        $this->resetArguments = [$migrations, $paths, $pretend];

        return [];
    }
}
