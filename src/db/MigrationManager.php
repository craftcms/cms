<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\errors\MigrationException;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\db\MigrationInterface;
use yii\di\Instance;

/**
 * MigrationManager manages a set of migrations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MigrationManager extends Component
{
    /**
     * The name of the dummy migration that marks the beginning of the whole migration history.
     */
    const BASE_MIGRATION = 'm000000_000000_base';

    /**
     * @since 3.5.0
     */
    const TRACK_CRAFT = 'craft';
    /**
     * @since 3.5.0
     */
    const TRACK_CONTENT = 'content';

    /**
     * @var string The migration track (e.g. `craft`, `content`, `plugin:commerce`, etc.)
     * @since 3.5.0
     */
    public $track;

    /**
     * @var string|null The namespace that the migration classes are in
     */
    public $migrationNamespace;

    /**
     * @var string|null The path to the migrations directory
     */
    public $migrationPath;

    /**
     * @var Connection|array|string The DB connection object or the application component ID of the DB connection
     */
    public $db = 'db';

    /**
     * @var string The migrations table name
     */
    public $migrationTable = Table::MIGRATIONS;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if ($this->migrationPath === null) {
            throw new InvalidConfigException('The migration path has not been set.');
        }

        $this->migrationPath = FileHelper::normalizePath(Craft::getAlias($this->migrationPath));
        $this->db = Instance::ensure($this->db, Connection::class);
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $name The migration name
     * @return MigrationInterface|\yii\db\Migration The migration instance
     * @throws Exception if the migration folder doesn't exist
     */
    public function createMigration(string $name)
    {
        if (!is_dir($this->migrationPath)) {
            throw new Exception("Can't instantiate migrations because the migration folder doesn't exist");
        }

        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';
        $class = $this->migrationNamespace . '\\' . $name;
        require_once $file;

        return new $class;
    }

    /**
     * Upgrades the application by applying new migrations.
     *
     * @param int $limit The number of new migrations to be applied. If 0, it means
     * applying all available new migrations.
     * @throws MigrationException on migrate failure
     */
    public function up(int $limit = 0)
    {
        // This might take a while
        App::maxPowerCaptain();

        $migrationNames = $this->getNewMigrations();

        if (empty($migrationNames)) {
            Craft::info('No new migration found. Your system is up-to-date.', __METHOD__);
            return;
        }

        $total = count($migrationNames);

        if ($limit !== 0) {
            $migrationNames = array_slice($migrationNames, 0, $limit);
        }

        $n = count($migrationNames);

        if ($n === $total) {
            $logMessage = "Total $n new " . ($n === 1 ? 'migration' : 'migrations') . ' to be applied:';
        } else {
            $logMessage = "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . ' to be applied:';
        }

        foreach ($migrationNames as $migrationName) {
            $logMessage .= "\n\t$migrationName";
        }

        Craft::info($logMessage, __METHOD__);

        foreach ($migrationNames as $migrationName) {
            try {
                $this->migrateUp($migrationName);
            } catch (MigrationException $e) {
                Craft::error('Migration failed. The rest of the migrations are cancelled.', __METHOD__);
                throw $e;
            }
        }

        Craft::info('Migrated up successfully.', __METHOD__);
    }

    /**
     * Downgrades the application by reverting old migrations.
     *
     * @param int $limit The number of migrations to be reverted. Defaults to 1,
     * meaning the last applied migration will be reverted. If set to 0, all migrations will be reverted.
     * @throws MigrationException on migrate failure
     */
    public function down(int $limit = 1)
    {
        // This might take a while
        App::maxPowerCaptain();

        $migrationNames = array_keys($this->getMigrationHistory($limit));

        if (empty($migrationNames)) {
            Craft::info('No migration has been done before.', __METHOD__);
            return;
        }

        $n = count($migrationNames);
        $logMessage = "Total $n " . ($n === 1 ? 'migration' : 'migrations') . ' to be reverted:';

        foreach ($migrationNames as $migrationName) {
            $logMessage .= "\n\t$migrationName";
        }

        Craft::info($logMessage, __METHOD__);

        foreach ($migrationNames as $migrationName) {
            try {
                $this->migrateDown($migrationName);
            } catch (MigrationException $e) {
                Craft::error('Migration failed. The rest of the migrations are cancelled.', __METHOD__);
                throw $e;
            }
        }

        Craft::info('Migrated down successfully.', __METHOD__);
    }

    /**
     * Upgrades with the specified migration.
     *
     * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to apply, or the migration itself
     * @throws InvalidConfigException if $migration is invalid
     * @throws MigrationException on migrate failure
     */
    public function migrateUp($migration)
    {
        list($migrationName, $migration) = $this->_normalizeMigration($migration);

        if ($migrationName === self::BASE_MIGRATION) {
            return;
        }

        /** @var \yii\db\Migration $migration */
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $migration = Instance::ensure($migration, MigrationInterface::class);

        // Clear the schema cache
        $schema = $this->db->getSchema();
        $schema->refresh();

        Craft::info("Applying $migrationName", __METHOD__);

        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

        if (!$isConsoleRequest) {
            ob_start();
        }

        $start = microtime(true);
        try {
            if ($migration instanceof Migration) {
                $success = ($migration->up(true) !== false);
            } else {
                $success = ($migration->up() !== false);
            }
        } catch (\Throwable $e) {
            $success = false;
        }
        $time = microtime(true) - $start;

        // Clear the schema cache
        $schema->refresh();

        $log = ($success ? 'Applied ' : 'Failed to apply ') . $migrationName . ' (time: ' . sprintf('%.3f', $time) . 's).';
        if (!$isConsoleRequest) {
            $output = ob_get_clean();
            $log .= " Output:\n" . $output;
        }

        if (!$success) {
            Craft::error($log, __METHOD__);
            throw new MigrationException($migration, $output ?? null, null, 0, $e ?? null);
        }

        Craft::info($log, __METHOD__);
        $this->addMigrationHistory($migrationName);
    }

    /**
     * Downgrades with the specified migration.
     *
     * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to revert, or the migration itself
     * @throws InvalidConfigException if $migration is invalid
     * @throws MigrationException on migrate failure
     */
    public function migrateDown($migration)
    {
        list($migrationName, $migration) = $this->_normalizeMigration($migration);

        if ($migrationName === self::BASE_MIGRATION) {
            return;
        }

        /** @var \yii\db\Migration $migration */
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $migration = Instance::ensure($migration, MigrationInterface::class);

        // Clear the schema cache
        $schema = $this->db->getSchema();
        $schema->refresh();

        Craft::info("Reverting $migrationName", __METHOD__);

        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

        if (!$isConsoleRequest) {
            ob_start();
        }

        $start = microtime(true);
        try {
            if ($migration instanceof Migration) {
                $success = ($migration->down(true) !== false);
            } else {
                $success = ($migration->down() !== false);
            }
        } catch (\Throwable $e) {
            $success = false;
        }
        $time = microtime(true) - $start;

        // Clear the schema cache
        $schema->refresh();

        $log = ($success ? 'Reverted ' : 'Failed to revert ') . $migrationName . ' (time: ' . sprintf('%.3f', $time) . 's).';
        if (!$isConsoleRequest) {
            $output = ob_get_clean();
            $log .= " Output:\n" . $output;
        }

        if (!$success) {
            Craft::error($log, __METHOD__);
            throw new MigrationException($migration, $output ?? null, null, 0, $e ?? null);
        }

        Craft::info($log, __METHOD__);
        $this->removeMigrationHistory($migrationName);
    }

    /**
     * Returns the migration history.
     *
     * @param int $limit The maximum number of records in the history to be returned. `null` for "no limit".
     * @return array The migration history
     */
    public function getMigrationHistory(int $limit = 0): array
    {
        $query = $this->_createMigrationQuery();
        if ($limit !== 0) {
            $query->limit($limit);
        }
        $history = $query->pairs($this->db);
        unset($history[self::BASE_MIGRATION]);

        return $history;
    }

    /**
     * Adds a new migration entry to the history.
     *
     * @param string $name The migration name
     * @throws NotSupportedException
     */
    public function addMigrationHistory(string $name)
    {
        // TODO: Remove after next breakpoint
        if ($this->db->columnExists(Table::MIGRATIONS, 'type')) {
            if ($this->track !== self::TRACK_CRAFT) {
                throw new NotSupportedException('Plugin and content migrations aren’t allowed until you update Craft.');
            }
            Db::insert($this->migrationTable, [
                'type' => 'app',
                'name' => $name,
                'applyTime' => Db::prepareDateForDb(new \DateTime()),
            ]);
            return;
        }

        Db::insert($this->migrationTable, [
            'track' => $this->track,
            'name' => $name,
            'applyTime' => Db::prepareDateForDb(new \DateTime()),
        ]);
    }

    /**
     * Removes an existing migration from the history.
     *
     * @param string $name The migration name
     */
    public function removeMigrationHistory(string $name)
    {
        Db::delete($this->migrationTable, [
            'track' => $this->track,
            'name' => $name,
        ]);
    }

    /**
     * Truncates the migration history.
     *
     * @since 3.0.32
     */
    public function truncateHistory()
    {
        Db::delete($this->migrationTable, [
            'track' => $this->track,
        ]);
    }

    /**
     * Returns whether a given migration has been applied.
     *
     * @param string $name The migration name
     * @return bool Whether the migration has been applied
     */
    public function hasRun(string $name): bool
    {
        return $this->_createMigrationQuery()
            ->andWhere(['name' => $name])
            ->exists($this->db);
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return array The list of new migrations
     */
    public function getNewMigrations(): array
    {
        $migrations = [];

        // Ignore if the migrations folder doesn't exist
        if (!is_dir($this->migrationPath)) {
            return $migrations;
        }

        $history = $this->getMigrationHistory();
        $handle = opendir($this->migrationPath);

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $this->migrationPath . DIRECTORY_SEPARATOR . $file;

            if (preg_match('/^(m\d{6}_\d{6}_.*?)\.php$/', $file, $matches) && is_file($path) && !isset($history[$matches[1]])) {
                $migrations[] = $matches[1];
            }
        }

        closedir($handle);
        sort($migrations);

        return $migrations;
    }

    /**
     * Normalizes the $migration argument passed to [[migrateUp()]] and [[migrateDown()]].
     *
     * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to apply, or the migration itself
     * @return array
     */
    private function _normalizeMigration($migration): array
    {
        if (is_string($migration)) {
            $migrationName = $migration;
            $migration = $this->createMigration($migration);
        } else {
            $classParts = explode('\\', get_class($migration));
            $migrationName = array_pop($classParts);
        }

        return [$migrationName, $migration];
    }

    /**
     * Returns a Query object prepped for retrieving migrations.
     *
     * @return Query The query
     */
    private function _createMigrationQuery(): Query
    {
        // TODO: Remove after next breakpoint
        if ($this->db->columnExists($this->migrationTable, 'version', true)) {
            $query = (new Query())
                ->select(['version as name', 'applyTime'])
                ->from([$this->migrationTable])
                ->orderBy(['name' => SORT_DESC]);

            if ($this->track === 'craft') {
                $query->where(['pluginId' => null]);
            } else {
                $query->where(new Expression('1 = 0'));
            }

            return $query;
        }

        // TODO: Remove after next breakpoint
        if ($this->db->columnExists($this->migrationTable, 'type', true)) {
            $query = (new Query())
                ->select(['name', 'applyTime'])
                ->from([$this->migrationTable])
                ->orderBy(['name' => SORT_DESC]);

            if ($this->track === 'craft') {
                $query->where(['type' => 'app']);
            } else {
                $query->where(new Expression('1 = 0'));
            }

            return $query;
        }

        return (new Query())
            ->select(['name', 'applyTime'])
            ->from([$this->migrationTable])
            ->orderBy(['name' => SORT_DESC])
            ->where(['track' => $this->track]);
    }
}
