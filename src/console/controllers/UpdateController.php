<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Composer\IO\BufferIO;
use Craft;
use craft\base\Plugin;
use craft\errors\InvalidPluginException;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\Update;
use craft\models\Updates;
use Ifsnop\Mysqldump\Mysqldump;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Updates Craft and plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.38
 */
class UpdateController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $defaultAction = 'update';

    /**
     * @var bool Force the update if allowUpdates is disabled
     */
    public $force = false;

    /**
     * @var bool Backup the database before updating
     */
    public $backup;

    /**
     * @var bool Run new database migrations after completing the update
     */
    public $migrate = true;

    /**
     * @var string|null The path to the database backup
     */
    private $_backupPath;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'update') {
            $options[] = 'force';
            $options[] = 'backup';
            $options[] = 'migrate';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        $aliases = parent::optionAliases();
        $aliases['f'] = 'force';
        return $aliases;
    }

    /**
     * Displays info about available updates.
     */
    public function actionInfo(): int
    {
        $updates = $this->_getUpdates(true);

        if (($total = $updates->getTotal()) === 0) {
            $this->stdout('You’re all up-to-date!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('You’ve got ', Console::FG_GREEN);
        $this->stdout($total === 1 ? 'one' : $total, Console::FG_GREEN, Console::BOLD);
        $this->stdout(' available update' . ($total === 1 ? '' : 's') . ':' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        if ($updates->cms->getHasReleases()) {
            $this->_outputUpdate('craft', Craft::$app->version, $updates->cms->getLatest()->version, $updates->cms->getHasCritical(), $updates->cms->status);
        }

        $pluginsService = Craft::$app->getPlugins();

        foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
            if ($pluginUpdate->getHasReleases()) {
                try {
                    $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                } catch (InvalidPluginException $e) {
                    continue;
                }
                if ($pluginInfo['isInstalled']) {
                    $this->_outputUpdate($pluginHandle, $pluginInfo['version'], $pluginUpdate->getLatest()->version, $pluginUpdate->getHasCritical(), $pluginUpdate->status);
                }
            }
        }

        $this->stdout(PHP_EOL . 'Run ');
        Console::outputCommand('update all');
        $this->stdout(' or ');
        Console::outputCommand('update <handle>');
        $this->stdout(' to perform an update.' . PHP_EOL . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Updates Craft and/or plugins.
     *
     * @param string $handle
     * The update handle (`all`, `craft`, or a plugin handle). You can pass
     * multiple handles separated by spaces, and you can update to a specific
     * version using the syntax <handle>:<version>`.
     * @return int
     */
    public function actionUpdate(string $handle = null): int
    {
        $handles = array_filter(func_get_args());

        if (empty($handles)) {
            return $this->runAction('info');
        }

        // Make sure updates are allowed
        if (!$this->_allowUpdates()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Figure out the new requirements
        $requirements = $this->_getRequirements(...$handles);
        if (empty($requirements)) {
            return ExitCode::OK;
        }

        // Try to backup the DB
        if (!$this->_backup()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run the update
        if (!$this->_performUpdate($requirements)) {
            $this->_revertComposerChanges();
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run migrations?
        if (!$this->_migrate()) {
            if ($this->_restoreDb()) {
                $this->_revertComposerChanges();
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Update complete!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Installs dependencies based on the current composer.json & composer.lock.
     *
     * @return int
     */
    public function actionComposerInstall(): int
    {
        $this->stdout('Performing Composer install ... ', Console::FG_YELLOW);
        $io = new BufferIO();

        $composerService = Craft::$app->getComposer();
        $composerService->disablePackagist = false;

        try {
            $composerService->install(null, $io, false);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $io->getOutput() . PHP_EOL . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns whether updates are allowed.
     *
     * @return bool
     */
    private function _allowUpdates(): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->allowUpdates && !$this->force) {
            if (!$this->interactive) {
                $this->stderr('Updates are disallowed for this environment. Pass --force to override.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }

            if (!$this->confirm('Updates are disallowed for this environment. Update anyway?')) {
                $this->stderr('Aborting update.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the new Composer requirements.
     *
     * @param string[] $handles
     * @return array
     */
    private function _getRequirements(string ...$handles): array
    {
        $updates = $this->_getUpdates();
        $pluginsService = Craft::$app->getPlugins();
        $info = [];
        $requirements = [];

        if ($handles === ['all']) {
            if (($latest = $updates->cms->getLatest()) !== null) {
                $this->_updateRequirements($requirements, $info, 'craft', Craft::$app->version, $latest->version, 'craftcms/cms', $updates->cms);
            }

            foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
                if (($latest = $pluginUpdate->getLatest()) !== null) {
                    try {
                        $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                    } catch (InvalidPluginException $e) {
                        continue;
                    }
                    if ($pluginInfo['isInstalled']) {
                        $this->_updateRequirements($requirements, $info, $pluginHandle, $pluginInfo['version'], $latest->version, $pluginInfo['packageName'], $pluginUpdate);
                    }
                }
            }
        } else {
            foreach ($handles as $handle) {
                if (strpos($handle, ':') !== false) {
                    list($handle, $to) = explode(':', $handle, 2);
                } else {
                    $to = null;
                }

                if ($handle === 'craft') {
                    $this->_updateRequirements($requirements, $info, $handle, Craft::$app->version, $to, 'craftcms/cms', $updates->cms);
                } else {
                    $pluginInfo = null;
                    if (isset($updates->plugins[$handle])) {
                        try {
                            $pluginInfo = $pluginsService->getPluginInfo($handle);
                        } catch (InvalidPluginException $e) {
                        }
                    }

                    if ($pluginInfo === null || !$pluginInfo['isInstalled']) {
                        $this->stdout('No plugin exists with the handle “' . $handle . '”.' . PHP_EOL, Console::FG_RED);
                        continue;
                    }

                    $this->_updateRequirements($requirements, $info, $handle, $pluginInfo['version'], $to, $pluginInfo['packageName'], $updates->plugins[$handle]);
                }
            }
        }

        if (($total = count($requirements)) !== 0) {
            $this->stdout('Performing ', Console::FG_GREEN);
            $this->stdout($total === 1 ? 'one' : $total, Console::FG_GREEN, Console::BOLD);
            $this->stdout(' update' . ($total === 1 ? '' : 's') . ':' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

            foreach ($info as list($handle, $from, $to, $critical, $status)) {
                $this->_outputUpdate($handle, $from, $to, $critical, $status);
            }

            $this->stdout(PHP_EOL);
        } else {
            $this->stdout('You’re all up-to-date!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return $requirements;
    }

    /**
     * Updates the requirements.
     *
     * @param array $requirements
     * @param array $info
     * @param string $handle
     * @param string $from
     * @param string|null $to
     * @param string $oldPackageName
     * @param Update $update
     */
    private function _updateRequirements(array &$requirements, array &$info, string $handle, string $from, string $to = null, string $oldPackageName, Update $update)
    {
        if ($update->status === Update::STATUS_EXPIRED) {
            $this->stdout("Skipping {$handle} because its license has expired." . PHP_EOL, Console::FG_GREY);
            return;
        }

        if ($to === null) {
            $to = $update->getLatest()->version ?? $from;
        }

        if ($to === $from) {
            $this->stdout("Skipping {$handle} because it’s already up-to-date." . PHP_EOL, Console::FG_GREY);
            return;
        }

        $requirements[$update->packageName] = $to;
        $info[] = [$handle, $from, $to, $update->getHasCritical(), $update->status];

        // Has the package name changed?
        if ($update->packageName !== $oldPackageName) {
            $requirements[$oldPackageName] = false;
        }
    }

    /**
     * Attempts to backup the database.
     *
     * @return bool
     */
    private function _backup(): bool
    {
        if (!$this->_shouldBackup()) {
            $this->stdout('Skipping database backup.' . PHP_EOL, Console::FG_GREY);
            return true;
        }

        $this->stdout('Backing up the database ... ', Console::FG_YELLOW);

        try {
            $this->_backupPath = Craft::$app->getDb()->backup();
        } catch (\Throwable $e) {
            $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);

            if (!$this->_backupWarning()) {
                $this->stderr('Aborting update.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }

            return true;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
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

    /**
     * Returns whether the database should be backed up
     *
     * @return bool
     */
    private function _shouldBackup(): bool
    {
        if (is_bool($this->backup)) {
            return $this->backup;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$this->interactive) {
            return $generalConfig->getBackupOnUpdate();
        }

        return $this->confirm('Backup the database?', $generalConfig->getBackupOnUpdate());
    }

    /**
     * Installs Composer packages.
     *
     * @param array $requirements
     * @return bool
     */
    private function _performUpdate(array $requirements): bool
    {
        $this->stdout('Performing update with Composer ... ', Console::FG_YELLOW);
        $io = new BufferIO();

        $composerService = Craft::$app->getComposer();
        $composerService->disablePackagist = false;

        try {
            $composerService->install($requirements, $io, false);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $io->getOutput() . PHP_EOL . PHP_EOL);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Attempts to run new migrations.
     *
     * @return bool
     */
    private function _migrate(): bool
    {
        if ($this->migrate === false) {
            $this->stdout('Skipping applying new migrations.' . PHP_EOL, Console::FG_GREY);
            return true;
        }

        try {
            $script = Craft::$app->getRequest()->getScriptFile();
        } catch (InvalidConfigException $e) {
            $this->stderr('Can’t apply new migrations: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            $this->stdout('You can apply new migrations manually by running ');
            Console::outputCommand('migrate/all --no-content');
            $this->stdout(PHP_EOL);
            return false;
        }

        $this->stdout('Applying new migrations ... ', Console::FG_YELLOW);

        $process = new Process([$script, 'migrate/all', '--no-content']);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $process->getOutput() . PHP_EOL . PHP_EOL);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Attempts to restore the database after a migration failure.
     *
     * @return bool
     */
    private function _restoreDb(): bool
    {
        if (
            !$this->_backupPath ||
            ($this->interactive && !$this->confirm('Restore the database backup?', true))
        ) {
            return false;
        }

        $this->stdout('Restoring the database backup ... ', Console::FG_YELLOW);

        try {
            Craft::$app->getDb()->restore($this->_backupPath);
        } catch (\Throwable $e) {
            $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            $this->stdout('You can manually restore the backup file located at ' . $this->_backupPath . PHP_EOL);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Reverts Composer changes.
     */
    private function _revertComposerChanges()
    {
        // See if we have composer.json and composer.lock backups
        $backupsDir = Craft::$app->getPath()->getComposerBackupsPath();
        $jsonBackup = $backupsDir . DIRECTORY_SEPARATOR . 'composer.json';
        $lockBackup = $backupsDir . DIRECTORY_SEPARATOR . 'composer.lock';

        if (!is_file($jsonBackup)) {
            $this->stdout("Can’t revert Composer changes because no composer.json backup exists in {$backupsDir}." . PHP_EOL, Console::FG_RED);
            return;
        }

        if (!is_file($lockBackup)) {
            $this->stdout("Can’t revert Composer changes because no composer.lock backup exists in {$backupsDir}." . PHP_EOL, Console::FG_RED);
            return;
        }

        $jsonContents = file_get_contents($jsonBackup);
        $lockContents = file_get_contents($lockBackup);

        // The composer.lock backup could be just a placeholder
        if (!array_key_exists('packages', Json::decode($lockContents))) {
            $this->stdout('Can’t revert Composer changes because no composer.lock file existed before the update.' . PHP_EOL, Console::FG_RED);
            return;
        }

        if ($this->interactive && !$this->confirm('Revert the Composer changes?', true)) {
            return;
        }

        $composerService = Craft::$app->getComposer();
        FileHelper::writeToFile($composerService->getJsonPath(), $jsonContents);
        FileHelper::writeToFile($composerService->getLockPath(), $lockContents);

        try {
            $script = Craft::$app->getRequest()->getScriptFile();
        } catch (InvalidConfigException $e) {
            $this->stderr('Can’t revert Composer changes: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            $this->stdout('You can revert Composer changes manually by running ');
            Console::outputCommand('update/composer-install');
            $this->stdout(PHP_EOL);
            return;
        }

        $this->stdout('Reverting Composer changes ... ', Console::FG_YELLOW);

        $process = new Process([$script, 'update/composer-install']);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $process->getOutput() . PHP_EOL . PHP_EOL);
            return;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Displays info for an update.
     *
     * @param string $handle
     * @param string $from
     * @param string $to
     * @param bool $critical
     * @param string $status
     */
    private function _outputUpdate(string $handle, string $from, string $to, bool $critical, string $status)
    {
        $expired = $status === Update::STATUS_EXPIRED;
        $grey = $expired ? Console::FG_GREY : null;

        $this->stdout('    - ', $grey ?? Console::FG_BLUE);
        $this->stdout($handle . ' ', $grey ?? Console::FG_CYAN);
        $this->stdout($from, $grey ?? Console::FG_PURPLE);
        $this->stdout(' => ', $grey ?? Console::FG_BLUE);
        $this->stdout($to, $grey ?? Console::FG_PURPLE);

        if ($critical) {
            $this->stdout(' (CRITICAL)', $grey ?? Console::FG_RED);
        }

        if ($expired) {
            $this->stdout(' (EXPIRED)', Console::FG_RED);
        }

        $this->stdout(PHP_EOL);
    }

    /**
     * Returns the available updates.
     *
     * @return Updates
     */
    private function _getUpdates(): Updates
    {
        $this->stdout('Fetching available updates ... ', Console::FG_YELLOW);
        $updates = Craft::$app->getUpdates()->getUpdates(true);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return $updates;
    }
}
