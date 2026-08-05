<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Aliases\Aliases;
use Override;

/**
 * @internal
 */
class Migrator extends \Illuminate\Database\Migrations\Migrator
{
    /** @var MigrationRepository */
    #[Override]
    protected $repository;

    public function track(string $track): self
    {
        if ($track === 'content') {
            $track = null;
        }

        $this->repository->track($track);

        if ($track === 'craft') {
            $this->setPaths(['@migrations']);
        }

        if (is_null($track)) {
            $this->setPaths([database_path('migrations')]);
        }

        return $this;
    }

    public function getTrack(): ?string
    {
        return $this->repository->getTrack();
    }

    /** @param list<string> $paths */
    public function setPaths(array $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    #[Override]
    public function run($paths = [], array $options = []): array
    {
        if (empty($paths)) {
            $paths = $this->paths;
        }

        $paths = array_map(Aliases::get(...), $paths);

        if (! $this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        return parent::run($paths, $options);
    }

    /**
     * @param  object  $migration
     * @param  string  $method
     * @param  string|null  $name
     */
    #[Override]
    public function runMigration($migration, $method, $name = null): void
    {
        parent::runMigration($migration, $method, $name);
    }

    /**
     * @param  list<string>  $migrations
     * @param  list<string>  $paths
     * @return list<string>
     */
    #[Override]
    public function resetMigrations(array $migrations, array $paths, $pretend = false): array
    {
        return parent::resetMigrations($migrations, $paths, $pretend);
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    public function getPendingMigrations(array $paths = []): array
    {
        if (empty($paths)) {
            $paths = $this->paths;
        }

        $paths = array_map(Aliases::get(...), $paths);

        $files = $this->getMigrationFiles($paths);

        return $this->pendingMigrations(
            $files, $this->repository->getRan()
        );
    }
}
