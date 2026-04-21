<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

#[Singleton]
class LaravelMigrations
{
    public function install(Migrator $migrator): void
    {
        $pendingMigrations = $this->reconcile($migrator);

        if (empty($pendingMigrations)) {
            return;
        }

        $migrator->run($pendingMigrations);
    }

    /**
     * Reconciles already-existing optional Laravel tables with the content
     * migration track and returns any optional Laravel migrations that still
     * need to run.
     *
     * @return string[]
     */
    public function reconcile(Migrator $migrator): array
    {
        $this->publishMigrationFiles();

        $migrationPaths = $this->migrationPaths();

        if (empty($migrationPaths)) {
            return [];
        }

        $originalTrack = $migrator->getTrack();

        try {
            $migrator->track('content');

            return $this->reconcilePendingMigrations(
                $migrator,
                $migrator->getPendingMigrations($migrationPaths),
            );
        } finally {
            $migrator->track($originalTrack ?? 'content');
        }
    }

    public function ensureSessionsTable(): void
    {
        if (Schema::hasTable(Table::SESSIONS)) {
            return;
        }

        Schema::create(Table::SESSIONS, function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function ensureMigrationTableTrackColumn(): void
    {
        if (! Schema::hasTable(Table::MIGRATIONS) || Schema::hasColumn(Table::MIGRATIONS, 'track')) {
            return;
        }

        Schema::table(Table::MIGRATIONS, function (Blueprint $table) {
            $table->string('track')->nullable()->after('id');
        });
    }

    private function publishMigrationFiles(): void
    {
        foreach ($this->migrationPatterns() as $command => $pattern) {
            $existingMigrations = $this->migrationFiles($pattern);

            if (! empty($existingMigrations)) {
                continue;
            }

            $exitCode = Artisan::call($command);

            if (! in_array($exitCode, [0, 1], true)) {
                throw new RuntimeException("Could not create the [$command] migration.");
            }

            $migrationWasCreated = ! empty($this->migrationFiles($pattern));

            if (! $migrationWasCreated) {
                throw new RuntimeException("Could not create the [$command] migration.");
            }
        }
    }

    private function migrationPaths(): array
    {
        $migrationPaths = [];

        foreach ($this->migrationPatterns() as $pattern) {
            $files = $this->migrationFiles($pattern);
            $migrationPaths = [...$migrationPaths, ...$files];
        }

        $migrationPaths = array_values(array_unique($migrationPaths));
        sort($migrationPaths);

        return $migrationPaths;
    }

    private function migrationPatterns(): array
    {
        return [
            'make:cache-table' => '*_create_cache_table.php',
            'make:queue-table' => sprintf('*_create_%s_table.php', config('queue.connections.database.table', 'jobs')),
            'make:queue-failed-table' => sprintf('*_create_%s_table.php', config('queue.failed.table', 'failed_jobs')),
            'make:queue-batches-table' => sprintf('*_create_%s_table.php', config('queue.batching.table', 'job_batches')),
            'make:session-table' => '*_create_sessions_table.php',
            'make:notifications-table' => '*_create_notifications_table.php',
        ];
    }

    private function migrationFiles(string $pattern): array
    {
        return File::glob(app()->databasePath("migrations/$pattern")) ?: [];
    }

    /**
     * @param  string[]  $pendingMigrations
     * @return string[]
     */
    private function reconcilePendingMigrations(Migrator $migrator, array $pendingMigrations): array
    {
        $batch = $migrator->getRepository()->getNextBatchNumber();

        foreach ($pendingMigrations as $key => $path) {
            if (! $this->migrationTableExists($path)) {
                continue;
            }

            $migrator->getRepository()->log($migrator->getMigrationName($path), $batch);
            unset($pendingMigrations[$key]);
        }

        return array_values($pendingMigrations);
    }

    private function migrationTableExists(string $path): bool
    {
        $migrationName = pathinfo($path, PATHINFO_FILENAME);

        return match (true) {
            str_ends_with($migrationName, '_create_cache_table') => Schema::hasTable('cache'),
            str_ends_with($migrationName, '_create_'.config('queue.connections.database.table', 'jobs').'_table') => Schema::hasTable(config('queue.connections.database.table', 'jobs')),
            str_ends_with($migrationName, '_create_'.config('queue.failed.table', 'failed_jobs').'_table') => Schema::hasTable(config('queue.failed.table', 'failed_jobs')),
            str_ends_with($migrationName, '_create_'.config('queue.batching.table', 'job_batches').'_table') => Schema::hasTable(config('queue.batching.table', 'job_batches')),
            str_ends_with($migrationName, '_create_sessions_table') => Schema::hasTable(Table::SESSIONS),
            str_ends_with($migrationName, '_create_notifications_table') => Schema::hasTable('notifications'),
            default => false,
        };
    }
}
