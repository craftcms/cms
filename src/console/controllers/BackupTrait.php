<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use Throwable;

/**
 * Updates Craft and plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 * @mixin Controller
 */
trait BackupTrait
{
    /**
     * @var string|null The path to the database backup
     */
    protected ?string $backupPath = null;

    /**
     * Attempts to backup the database.
     *
     * @param bool|null $flag The user’s indication of whether they want the DB to be backed up
     * @return bool
     */
    protected function backup(?bool $flag = null): bool
    {
        if (!$this->_shouldBackup($flag)) {
            $this->stdout('Skipping database backup.' . PHP_EOL, Console::FG_GREY);
            return true;
        }

        $this->stdout('Backing up the database ... ', Console::FG_YELLOW);

        try {
            $this->backupPath = Craft::$app->getDb()->backup();
        } catch (Throwable $e) {
            $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);

            if (!$this->_backupWarning()) {
                $this->stderr('Aborting.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }

            return true;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Returns whether the database should be backed up
     *
     * @param bool|null $flag The user’s indication of whether they want the DB to be backed up
     * @return bool
     */
    private function _shouldBackup(?bool $flag): bool
    {
        if ($flag !== null) {
            return $flag;
        }

        $backupOnUpdate = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();

        if (!$this->interactive) {
            return $backupOnUpdate;
        }

        return $this->confirm('Create database backup?', $backupOnUpdate);
    }

    /**
     * Outputs a warning about creating a database backup.
     *
     * @return bool
     */
    private function _backupWarning(): bool
    {
        if (!$this->interactive) {
            return false;
        }

        Console::outputWarning('Please backup your database before continuing.');
        return $this->confirm('Ready to continue?');
    }
}
