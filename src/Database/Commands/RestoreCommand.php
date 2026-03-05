<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Database\Commands\Concerns\ManagesDatabaseTables;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\File;
use Override;
use Symfony\Component\Mime\MimeTypes;
use ZipArchive;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class RestoreCommand extends Command
{
    use CraftCommand;
    use ManagesDatabaseTables;

    #[Override]
    protected $signature = 'craft:db:restore {path?}
        {--drop-all-tables : Whether to drop all preexisting tables in the database prior to restoring the backup.}
        {--dropAllTables : Whether to drop all preexisting tables in the database prior to restoring the backup. [DEPRECATED]}
        {--format= : The input format that should be used (`custom`, `directory`, `tar`, or `plain`) for PostgreSQL backups.}';

    #[Override]
    protected $description = 'Restores a database backup.';

    #[Override]
    protected $aliases = ['db/restore', 'restore', 'restore:db', 'restore/db'];

    public function handle(Backups $backups, Connection $connection): int
    {
        $path = $this->argument('path');

        if (is_null($path)) {
            $path = $this->resolveBackupPath();
        }

        if (! is_string($path) || ! is_readable($path)) {
            $this->components->error("Backup path doesn't exist: $path");

            return self::FAILURE;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip') {
            $zip = new ZipArchive;

            if ($zip->open($path) !== true) {
                $this->components->error("Unable to open the zip file at $path.");

                return self::FAILURE;
            }

            $tempDir = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.Str::random(10);
            File::ensureDirectoryExists($tempDir);
            $this->components->task('Extracting zip to a temp directory', function () use ($zip, $tempDir): void {
                $zip->extractTo($tempDir);
                $zip->close();
            });

            $files = FileHelper::findFiles($tempDir);

            if (empty($files)) {
                $this->components->error("No files unzipped from $path.");
                FileHelper::removeDirectory($tempDir);

                return self::FAILURE;
            }

            $path = reset($files);
        }

        if ($this->tablesExist($connection)) {
            $this->maybeBackupDatabase();

            $dropAllTables = $this->option('drop-all-tables') || $this->option('dropAllTables');

            if ($dropAllTables || ($this->input->isInteractive() && confirm('Drop all tables from the database first?'))) {
                $this->dropAllTables($connection);
            }
        }

        $this->components->task('Restoring database backup', function () use ($backups, $path, $connection) {
            $restoreFormat = null;

            if ($connection->isPgsql()) {
                $restoreFormat = $this->option('format') ?? match (new MimeTypes()->guessMimeType($path)) {
                    'application/octet-stream' => 'custom',
                    'application/x-tar' => 'tar',
                    'directory' => 'directory',
                    default => null,
                };
            }

            $backups->restore(
                filePath: $path,
                connection: $connection,
                restoreFormat: $restoreFormat,
            );
        });

        if (isset($tempDir)) {
            $this->components->task('Deleting the temp directory', function () use ($tempDir) {
                FileHelper::removeDirectory($tempDir);
            });
        }

        if ($this->input->isInteractive() && confirm('Clear data caches?')) {
            $this->call('craft:clear-caches:data');
        }

        return self::SUCCESS;
    }

    private function resolveBackupPath(): ?string
    {
        if (! $this->input->isInteractive()) {
            return null;
        }

        $backupPaths = $this->defaultBackupPaths();

        if (empty($backupPaths)) {
            return null;
        }

        return select(
            label: 'Select a database backup to restore',
            options: collect($backupPaths)
                ->mapWithKeys(fn (string $backupPath) => [$backupPath => basename($backupPath)])
                ->all(),
        );
    }

    /**
     * @return list<string>
     */
    private function defaultBackupPaths(): array
    {
        $backupPath = Craft::$app->getPath()->getDbBackupPath(false);

        if (! File::isDirectory($backupPath)) {
            return [];
        }

        $backupExtensions = ['sql', 'dump', 'tar'];
        $paths = collect($backupExtensions)
            ->flatMap(fn (string $extension) => [
                ...(File::glob("$backupPath/*.$extension") ?: []),
                ...(File::glob("$backupPath/*.$extension.zip") ?: []),
            ])
            ->unique()
            ->filter(fn (string $candidatePath) => File::isFile($candidatePath) || File::isDirectory($candidatePath))
            ->sortByDesc(fn (string $candidatePath) => File::lastModified($candidatePath))
            ->values()
            ->all();

        /** @var list<string> $paths */
        return $paths;
    }
}
