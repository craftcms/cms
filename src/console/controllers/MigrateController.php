<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\PluginInterface;
use craft\console\ControllerTrait;
use craft\db\MigrationManager;
use craft\errors\InvalidPluginException;
use craft\errors\MigrateException;
use craft\events\RegisterMigratorEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\console\controllers\BaseMigrateController;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\db\MigrationInterface;
use yii\helpers\Console;

/**
 * Manages Craft and plugin migrations.
 *
 * A migration means a set of persistent changes to the application environment that is shared among different
 * developers. For example, in an application backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 * This controller provides support for tracking the migration history, updating migrations, and creating new
 * migration skeleton files.
 * The migration history is stored in a database table named `migrations`. The table will be automatically
 * created the first time this controller is executed, if it does not exist.
 * Below are some common usages of this command:
 * ~~~
 * # creates a new migration named 'create_user_table' for a plugin with the handle pluginHandle.
 * craft migrate/create create_user_table --plugin=pluginHandle
 * # applies ALL new migrations for a plugin with the handle pluginHandle
 * craft migrate up --plugin=pluginHandle
 * ~~~
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MigrateController extends BaseMigrateController
{
    use ControllerTrait;
    use BackupTrait;

    /**
     * @event RegisterMigratorEvent The event that is triggered when resolving an unknown migration track.
     *
     * ```php
     * use craft\console\controllers\MigrateController;
     * use craft\db\MigrationManager;
     * use craft\events\RegisterMigratorEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     MigrateController::class,
     *     MigrateController::EVENT_REGISTER_MIGRATOR,
     *     function(RegisterMigratorEvent $event) {
     *         if ($event->track === 'myCustomTrack') {
     *             $event->migrator = Craft::createObject([
     *                 'class' => MigrationManager::class,
     *                 'track' => 'myCustomTrack',
     *                 'migrationNamespace' => 'my\migration\namespace',
     *                 'migrationPath' => '/path/to/migrations',
     *             ]);
     *             $event->handled = true;
     *         }
     *     }
     * );
     * ```
     *
     * @since 3.5.0
     */
    public const EVENT_REGISTER_MIGRATOR = 'registerMigrator';

    /**
     * @var string|null The migration track to work with (e.g. `craft`, `content`, `plugin:commerce`, etc.)
     *
     * Defaults to `content`, or automatically set to the plugin’s track when `--plugin` is passed.
     * @since 3.5.0
     */
    public ?string $track = MigrationManager::TRACK_CONTENT;

    /**
     * @var string|null DEPRECATED. Use `--track` instead.
     * @deprecated in 3.5.0. Use [[track]] instead.
     */
    public ?string $type = null;

    /**
     * @var string|PluginInterface|null The handle of the plugin to use during migration operations, or the plugin itself.
     */
    public PluginInterface|string|null $plugin = null;

    /**
     * @var bool Exclude pending content migrations.
     */
    public bool $noContent = false;

    /**
     * @var bool Skip backing up the database.
     * @since 3.4.3
     */
    public bool $noBackup = false;

    /**
     * @var MigrationManager[] Migration managers that will be used in this request
     */
    private array $_migrators;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->checkTty();

        $this->templateFile = Craft::getAlias('@app/updates/migration.php.template');
    }

    /**
     * Returns the names of valid options for the action (id)
     * An option requires the existence of a public member variable whose
     * name is the option name.
     * Child classes may override this method to specify possible options.
     *
     * Note that the values setting via options are not available
     * until [[beforeAction()]] is being called.
     *
     * @param string $actionID the action id of the current request
     * @return string[] the names of the options valid for the action
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        // Remove options we end up overriding
        ArrayHelper::removeValue($options, 'migrationPath');
        ArrayHelper::removeValue($options, 'migrationNamespaces');
        ArrayHelper::removeValue($options, 'compact');

        if ($actionID === 'all') {
            $options[] = 'noBackup';
            $options[] = 'noContent';
        } else {
            $options[] = 'track';
            $options[] = 'plugin';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        $aliases = parent::optionAliases();
        $aliases['p'] = 'plugin';

        return $aliases;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Make sure this isn't a root user
        if (!$this->checkRootUser()) {
            return false;
        }

        if ($action->id !== 'all') {
            if ($this->plugin) {
                $this->track = "plugin:$this->plugin";
            } elseif ($this->track && preg_match('/^plugin:([\w\-]+)$/', $this->track, $match)) {
                $this->plugin = $match[1];
            }

            // Validate $plugin
            if ($this->plugin) {
                // Make sure $this->plugin in set to a valid plugin handle
                if (empty($this->plugin)) {
                    $this->stderr('You must specify the plugin handle using the --plugin option.' . PHP_EOL, Console::FG_RED);
                    return false;
                }
                try {
                    $this->plugin = $this->_plugin($this->plugin);
                } catch (InvalidPluginException) {
                    $this->stderr("Invalid plugin handle: $this->plugin" . PHP_EOL, Console::FG_RED);
                    return false;
                }
            }

            $this->migrationPath = $this->getMigrator()->migrationPath;
            FileHelper::createDirectory($this->migrationPath);
        }

        // Make sure that the project config YAML exists in case any migrations need to check incoming YAML values
        $projectConfig = Craft::$app->getProjectConfig();
        if ($projectConfig->writeYamlAutomatically && !$projectConfig->getDoesExternalConfigExist()) {
            $projectConfig->regenerateExternalConfig();
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * Creates a new migration.
     *
     * This command creates a new migration using the available migration template.
     * After using this command, developers should modify the created migration
     * skeleton by filling up the actual migration logic.
     *
     * ```
     * craft migrate/create create_news_section
     * ```
     *
     * By default, the migration is created in the project’s `migrations/`
     * folder (as a “content migration”).\
     * Use `--plugin=<plugin-handle>` to create a new plugin migration.\
     * Use `--type=app` to create a new Craft CMS app migration.
     *
     * @param string $name the name of the new migration. This should only contain
     * letters, digits, and underscores.
     * @return int
     * @throws Exception if the name argument is invalid.
     */
    public function actionCreate($name): int
    {
        if (!preg_match('/^\w+$/', $name)) {
            throw new Exception('The migration name should contain letters, digits and/or underscore characters only.');
        }

        if ($isInstall = (strcasecmp($name, 'install') === 0)) {
            $name = 'Install';
        } else {
            $name = 'm' . gmdate('ymd_His') . '_' . $name;
        }

        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';

        if (!$this->interactive || $this->confirm("Create new migration '$file'?", true)) {
            $templateFile = Craft::getAlias($this->templateFile);

            if ($templateFile === false) {
                throw new Exception('There was a problem getting the template file path');
            }

            $content = $this->renderFile($templateFile, [
                'isInstall' => $isInstall,
                'namespace' => $this->getMigrator()->migrationNamespace,
                'className' => $name,
            ]);

            FileHelper::writeToFile($file, $content);
            $this->stdout('New migration created successfully.' . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    /**
     * Runs all pending Craft, plugin, and content migrations.
     *
     * @return int
     * @throws MigrateException
     */
    public function actionAll(): int
    {
        if ($this->noContent) {
            $this->stdout("Checking for pending Craft and plugin migrations ...\n");
        } else {
            $this->stdout("Checking for pending migrations ...\n");
        }

        $migrationsByTrack = [];
        $updatesService = Craft::$app->getUpdates();

        $craftMigrations = Craft::$app->getMigrator()->getNewMigrations();
        if (!empty($craftMigrations) || $updatesService->getIsCraftUpdatePending()) {
            $migrationsByTrack[MigrationManager::TRACK_CRAFT] = $craftMigrations;
        }

        $pluginsService = Craft::$app->getPlugins();
        $plugins = $pluginsService->getAllPlugins();
        foreach ($plugins as $plugin) {
            $pluginMigrations = $plugin->getMigrator()->getNewMigrations();
            if (!empty($pluginMigrations) || $pluginsService->isPluginUpdatePending($plugin)) {
                $migrationsByTrack["plugin:$plugin->id"] = $pluginMigrations;
            }
        }

        if (!$this->noContent) {
            $contentMigrations = Craft::$app->getContentMigrator()->getNewMigrations();
            if (!empty($contentMigrations)) {
                $migrationsByTrack[MigrationManager::TRACK_CONTENT] = $contentMigrations;
            }
        }

        if (empty($migrationsByTrack)) {
            $this->stdout('No new migrations found. Your system is up to date.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $total = 0;

        foreach ($migrationsByTrack as $track => $migrations) {
            $n = count($migrations);

            $which = match ($track) {
                MigrationManager::TRACK_CRAFT => 'Craft',
                MigrationManager::TRACK_CONTENT => 'content',
                default => $plugins[substr($track, 7)]->name,
            };

            $this->stdout("Total $n new $which " . ($n === 1 ? 'migration' : 'migrations') . ' to be applied:' . PHP_EOL, Console::FG_YELLOW);
            foreach ($migrations as $migration) {
                $this->stdout("    - $migration" . PHP_EOL);
            }
            $this->stdout(PHP_EOL);

            $total += $n;
        }

        if ($this->interactive && !$this->confirm('Apply the above ' . ($total === 1 ? 'migration' : 'migrations') . '?')) {
            return ExitCode::OK;
        }

        // Enable maintenance mode
        Craft::$app->enableMaintenanceMode();

        // Backup the DB
        if (!$this->noBackup && !$this->backup()) {
            Craft::$app->disableMaintenanceMode();
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $applied = 0;

        foreach ($migrationsByTrack as $track => $migrations) {
            $this->track = $track;

            foreach ($migrations as $migration) {
                if (!$this->migrateUp($migration)) {
                    $this->stdout(PHP_EOL . "$applied from $total " . ($applied === 1 ? 'migration was' : 'migrations were') . ' applied.' . PHP_EOL, Console::FG_RED);
                    $this->stdout(PHP_EOL . 'Migration failed. The rest of the migrations are canceled.' . PHP_EOL, Console::FG_RED);
                    Craft::$app->disableMaintenanceMode();
                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $applied++;
            }

            // Update version info
            if ($track === MigrationManager::TRACK_CRAFT) {
                Craft::$app->getUpdates()->updateCraftVersionInfo();
            } elseif ($track !== MigrationManager::TRACK_CONTENT) {
                Craft::$app->getPlugins()->updatePluginVersionInfo($plugins[substr($track, 7)]);
            }
        }

        $this->stdout(PHP_EOL . "$total " . ($total === 1 ? 'migration was' : 'migrations were') . ' applied.' . PHP_EOL, Console::FG_GREEN);
        $this->stdout(PHP_EOL . 'Migrated up successfully.' . PHP_EOL, Console::FG_GREEN);
        Craft::$app->disableMaintenanceMode();
        $this->_clearCompiledTemplates();
        return ExitCode::OK;
    }

    /**
     * Upgrades Craft by applying new migrations.
     *
     * Example:
     * ```
     * php craft migrate     # apply all new migrations
     * php craft migrate 3   # apply the first 3 new migrations
     * ```
     *
     * @param int $limit The number of new migrations to be applied. If `0`, every new migration
     * will be applied.
     *
     * @return int the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionUp($limit = 0): int
    {
        switch ($this->track) {
            case MigrationManager::TRACK_CRAFT:
                $this->stdout("Checking for pending Craft migrations ...\n");
                break;
            case MigrationManager::TRACK_CONTENT:
                $this->stdout("Checking for pending content migrations ...\n");
                break;
            default:
                if ($this->plugin instanceof PluginInterface) {
                    $this->stdout("Checking for pending {$this->plugin->name} migrations ...\n");
                }
        }

        $res = parent::actionUp($limit);

        if ($res === ExitCode::OK && empty($this->getNewMigrations())) {
            // Update any schema versions.
            if ($this->track === MigrationManager::TRACK_CRAFT) {
                Craft::$app->getUpdates()->updateCraftVersionInfo();
            } elseif ($this->plugin) {
                Craft::$app->getPlugins()->updatePluginVersionInfo($this->plugin);
            }

            $this->_clearCompiledTemplates();
        }

        return $res;
    }

    /**
     * Returns a plugin by its handle.
     *
     * @param string $handle
     * @return PluginInterface
     * @throws InvalidPluginException
     */
    private function _plugin(string $handle): PluginInterface
    {
        $pluginsService = Craft::$app->getPlugins();
        if ($plugin = $pluginsService->getPlugin($handle)) {
            return $plugin;
        }
        return $pluginsService->createPlugin($handle);
    }

    /**
     * Clears all compiled templates.
     */
    private function _clearCompiledTemplates(): void
    {
        try {
            FileHelper::clearDirectory(Craft::$app->getPath()->getCompiledTemplatesPath(false));
        } catch (InvalidArgumentException) {
            // the directory doesn't exist
        } catch (ErrorException $e) {
            Craft::error('Could not delete compiled templates: ' . $e->getMessage());
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    /**
     * Returns a migration manager.
     *
     * @param string|null $track
     * @return MigrationManager
     * @throws InvalidPluginException
     * @throws InvalidConfigException
     */
    public function getMigrator(?string $track = null): MigrationManager
    {
        if ($track === null) {
            $track = $this->track;
        }

        if (!isset($this->_migrators[$track])) {
            if (preg_match('/^plugin:([\w\-]+)$/', $track, $match)) {
                $this->_migrators[$track] = $this->_plugin($match[1])->getMigrator();
            } else {
                switch ($track) {
                    case MigrationManager::TRACK_CRAFT:
                        $this->_migrators[$track] = Craft::$app->getMigrator();
                        break;
                    case MigrationManager::TRACK_CONTENT:
                        $this->_migrators[$track] = Craft::$app->getContentMigrator();
                        break;
                    default:
                        // Give plugins & modules a chance to register a custom migrator
                        $event = new RegisterMigratorEvent([
                            'track' => $track,
                        ]);
                        $this->trigger(self::EVENT_REGISTER_MIGRATOR, $event);
                        if (!$event->migrator) {
                            throw new InvalidConfigException("Invalid migration track: $track");
                        }
                        $this->_migrators[$track] = $event->migrator;
                }
            }
        }

        return $this->_migrators[$track];
    }

    /**
     * @inheritdoc
     */
    protected function createMigration($class): MigrationInterface
    {
        return $this->getMigrator()->createMigration($class);
    }

    /**
     * @inheritdoc
     */
    protected function getNewMigrations(): array
    {
        return $this->getMigrator()->getNewMigrations();
    }

    /**
     * @inheritdoc
     */
    protected function getMigrationHistory($limit): array
    {
        $history = $this->getMigrator()->getMigrationHistory((int)$limit);

        // Convert values to unix timestamps
        return array_map('strtotime', $history);
    }

    /**
     * @inheritdoc
     */
    protected function addMigrationHistory($version): void
    {
        $this->getMigrator()->addMigrationHistory($version);
    }

    /**
     * @inheritdoc
     */
    protected function removeMigrationHistory($version): void
    {
        $this->getMigrator()->removeMigrationHistory($version);
    }

    /**
     * Not supported.
     */
    public function actionFresh(): int
    {
        $this->stderr('This command is not supported.' . PHP_EOL, Console::FG_RED);
        return ExitCode::OK;
    }

    /**
     * @inheritdoc
     */
    protected function truncateDatabase(): void
    {
        throw new NotSupportedException('This command is not implemented in ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function stdout($string): bool|int
    {
        if (str_starts_with($string, 'Yii Migration Tool')) {
            return false;
        }
        return parent::stdout(...func_get_args());
    }
}
