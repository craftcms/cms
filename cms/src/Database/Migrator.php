<?php

namespace CraftCms\Cms\Database;

use CraftCms\Aliases\Facades\Aliases;

final class Migrator extends \Illuminate\Database\Migrations\Migrator
{
    /** @var \CraftCms\Cms\Database\MigrationRepository */
    protected $repository;

    public function track(?string $track): self
    {
        $this->repository->track($track);

        if ($track === 'craft') {
            $this->setPaths(['@migrations']);
        }

        return $this;
    }

    public function setPaths(array $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    public function run($paths = [], array $options = []): array
    {
        if (empty($paths)) {
            $paths = $this->paths;
        }

        $paths = array_map(fn (string $path) => Aliases::get($path), $paths);

        if (! $this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        return parent::run($paths, $options);
    }

    public function runMigration($migration, $method): void
    {
        parent::runMigration($migration, $method);
    }

    public function resetMigrations(array $migrations, array $paths, $pretend = false): array
    {
        return parent::resetMigrations($migrations, $paths, $pretend);
    }

    public function getPendingMigrations($paths = []): array
    {
        if (empty($paths)) {
            $paths = $this->paths;
        }

        $paths = array_map(fn (string $path) => Aliases::get($path), $paths);

        $files = $this->getMigrationFiles($paths);

        return $this->pendingMigrations(
            $files, $this->repository->getRan()
        );
    }
}
