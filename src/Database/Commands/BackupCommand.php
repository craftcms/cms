<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use craft\helpers\FileHelper;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Translation\I18N;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Override;
use Symfony\Component\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;

class BackupCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:db:backup {path?}
        {--zip : Whether the backup should be saved as a zip file.}
        {--format= : The output format that should be used (`custom`, `directory`, `tar`, or `plain`) for PostgreSQL backups.}
        {--overwrite : Whether to overwrite an existing backup file, if a specific file path is given.}';

    #[Override]
    protected $description = 'Creates a new database backup.';

    #[Override]
    protected $aliases = ['db/backup'];

    public function handle(Backups $backups, I18N $i18N): int
    {
        $this->components->info('Backing up the database ...');

        $connection = DB::connection();
        $path = $this->argument('path');
        $path = is_string($path) ? $path : null;

        $backupFormat = null;
        if ($connection->isPgsql()) {
            $backupFormat = $this->option('format');
        }

        if (is_string($path)) {
            if (! new Filesystem()->isAbsolutePath($path)) {
                $path = getcwd().DIRECTORY_SEPARATOR.$path;
            }

            $path = FileHelper::normalizePath($path);

            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR.basename($backups->getBackupFilePath(
                    connection: $connection,
                    backupFormat: $backupFormat,
                ));
            } elseif ($this->option('zip')) {
                $path = preg_replace('/\\.zip$/', '', $path) ?? $path;
            }
        } else {
            $path = $backups->getBackupFilePath(
                connection: $connection,
                backupFormat: $backupFormat,
            );
        }

        $checkPaths = [$path];

        if ($this->option('zip')) {
            $checkPaths[] = "$path.zip";
        }

        foreach ($checkPaths as $checkPath) {
            if (! File::isFile($checkPath)) {
                continue;
            }

            if (! $this->option('overwrite')) {
                if (! $this->input->isInteractive()) {
                    $this->components->error("$checkPath already exists. Retry with the --overwrite flag to overwrite it.");

                    return self::FAILURE;
                }

                if (! confirm("$checkPath already exists. Overwrite?")) {
                    $this->components->warn('Aborting');

                    return self::SUCCESS;
                }
            }

            File::delete($checkPath);
        }

        $backups->backupTo(
            filePath: $path,
            connection: $connection,
            backupFormat: $backupFormat,
        );

        if ($this->option('zip')) {
            $zipPath = FileHelper::zip($path);
            File::delete($path);
            $path = $zipPath;
        }

        $this->components->success('Database backup completed.');

        if (File::isDirectory($path)) {
            $this->components->info("Backup directory: $path");

            return self::SUCCESS;
        }

        $size = $i18N->getFormatter()->asShortSize(File::size($path));
        $this->components->info("Backup file: $path ($size)");

        return self::SUCCESS;
    }
}
