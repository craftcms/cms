<?php

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Cms;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

trait BackupTrait
{
    protected string $backupPath;

    protected function backup(?bool $flag = null): bool
    {
        if (! $this->shouldBackup($flag)) {
            info('Skipping database backup.');

            return true;
        }

        $result = spin(
            callback: function () {
                try {
                    $this->backupPath = \Craft::$app->getDb()->backup();
                } catch (Throwable $e) {
                    error('error: '.$e->getMessage());

                    if (! $this->backupWarning()) {
                        error('Aborting. ');

                        return false;
                    }

                    return true;
                }

                return true;
            },
            message: 'Backing up the database...'
        );

        $result
            ? $this->components->success('Database backup successful')
            : $this->components->error('Database backup failed');

        return $result;
    }

    /**
     * Returns whether the database should be backed up
     *
     * @param  bool|null  $flag  The user’s indication of whether they want the DB to be backed up
     */
    private function shouldBackup(?bool $flag): bool
    {
        if ($flag !== null) {
            return $flag;
        }

        $backupOnUpdate = Cms::config()->getBackupOnUpdate();

        if (! $this->input->isInteractive()) {
            return $backupOnUpdate;
        }

        return confirm('Create database backup?', $backupOnUpdate);
    }

    /**
     * Outputs a warning about creating a database backup.
     */
    private function backupWarning(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        $this->warn('Please backup your database before continuing.');

        return $this->components->confirm('Ready to continue?');
    }

    /**
     * Attempts to restore the database after a migration failure.
     */
    protected function restore(): bool
    {
        if (
            ! $this->backupPath ||
            ($this->input->isInteractive() && ! $this->confirm("\nRestore the database backup?", true))
        ) {
            return false;
        }

        $this->warn('Restoring the database backup ... ');

        try {
            \Craft::$app->getDb()->restore($this->backupPath);
        } catch (Throwable $e) {
            $this->error('error: '.$e->getMessage());
            $this->error('You can manually restore the backup file located at '.$this->backupPath);

            return false;
        }

        $this->info('done');

        return true;
    }
}
