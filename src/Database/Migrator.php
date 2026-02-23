<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Aliases\Aliases;
use Override;

/**
 * @internal
 */
final class Migrator extends \Illuminate\Database\Migrations\Migrator
{
    /** @var \CraftCms\Cms\Database\MigrationRepository */
    #[\Override]
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

    #[Override]
    public function runMigration($migration, $method): void
    {
        parent::runMigration($migration, $method);
    }

    #[Override]
    public function resetMigrations(array $migrations, array $paths, $pretend = false): array
    {
        return parent::resetMigrations($migrations, $paths, $pretend);
    }

    public function getPendingMigrations($paths = []): array
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
