<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\console\controllers;

use Craft;
use craft\app\base\Plugin;
use craft\app\db\MigrationManager;
use craft\app\helpers\Io;
use yii\console\controllers\BaseMigrateController;
use yii\console\Exception;
use yii\helpers\Console;

/**
 * Manages Craft and plugin migrations.
 *
 * A migration means a set of persistent changes to the application environment that is shared among different
 * developers. For example, in an application backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 *
 * This controllers provides support for tracking the migration history, updating migrations, and creating new
 * migration skeleton files.
 *
 * The migration history is stored in a database table named [[migrationTable]]. The table will be automatically
 * created the first time this controller is executed, if it does not exist.
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table' for a plugin with the handle pluginHandle.
 * craft migrate/create create_user_table --plugin=pluginHandle
 *
 * # applies ALL new migrations for a plugin with the handle pluginHandle
 * craft migrate up --plugin=pluginHandle
 * ~~~
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MigrateController extends BaseMigrateController
{
    // Properties
    // =========================================================================

    /**
     * @var string The type of migrations we're dealing with here. Can be 'app', 'plugin', or 'content'.
     *
     * If [[plugin]] is defined, this will automatically be set to 'plugin'. Otherwise defaults to 'app'.
     */
    public $type;

    /**
     * @var string|Plugin The handle of the plugin to use during migration operations, or the plugin itself
     */
    public $plugin;

    /**
     * @var MigrationManager The migration manager that will be used in this request
     */
    private $_migrator;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->templateFile = Craft::getAlias('@app/updates/migrationtemplate').'.php';
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['type', 'plugin']
        );
    }

    /**
     * @inheritdoc
     *
     * @throws Exception if the 'plugin' option isn't valid
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Validate $type
        if ($this->type) {
            if (!in_array($this->type, [MigrationManager::TYPE_APP, MigrationManager::TYPE_PLUGIN, MigrationManager::TYPE_CONTENT])) {
                throw new Exception('Invalid migration type: '.$this->type);
            }
        } else {
            if ($this->plugin) {
                $this->type = MigrationManager::TYPE_PLUGIN;
            } else {
                $this->type = MigrationManager::TYPE_CONTENT;
            }
        }

        switch ($this->type) {
            case MigrationManager::TYPE_CONTENT:
                // Verify that a content migrations folder exists
                if (Craft::getAlias("@contentMigrations") == false) {
                    throw new Exception('You must create a migrations/ folder within your craft/ folder before managing content migrations');
                }
                break;
            case MigrationManager::TYPE_PLUGIN:
                // Make sure $this->plugin in set to a plugin
                if (is_string($this->plugin)) {
                    $plugin = Craft::$app->getPlugins()->getPlugin($this->plugin);

                    if ($plugin === null) {
                        throw new Exception('Invalid plugin handle: '.$this->plugin);
                    }

                    $this->plugin = $plugin;
                }
        }

        $this->migrationPath = $this->getMigrator()->migrationPath;
        Io::ensureFolderExists($this->migrationPath);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function actionCreate($name)
    {
        if (!preg_match('/^\w+$/', $name)) {
            throw new Exception("The migration name should contain letters, digits and/or underscore characters only.");
        }

        $name = 'm'.gmdate('ymd_His').'_'.$name;
        $file = $this->migrationPath.DIRECTORY_SEPARATOR.$name.'.php';

        if ($this->confirm("Create new migration '$file'?")) {
            $content = $this->renderFile(Craft::getAlias($this->templateFile), [
                'namespace' => $this->getMigrator()->migrationNamespace,
                'className' => $name
            ]);

            Io::writeToFile($file, $content);
            $this->stdout("New migration created successfully.\n", Console::FG_GREEN);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the migration manager that should be used for this request
     *
     * @return MigrationManager
     */
    protected function getMigrator()
    {
        if ($this->_migrator === null) {
            switch ($this->type) {
                case MigrationManager::TYPE_APP:
                    $this->_migrator = Craft::$app->getMigrator();
                    break;
                case MigrationManager::TYPE_CONTENT:
                    $this->_migrator = Craft::$app->getContentMigrator();
                    break;
                case MigrationManager::TYPE_PLUGIN:
                    $this->_migrator = $this->plugin->getMigrator();
                    break;
            }
        }

        return $this->_migrator;
    }

    /**
     * @inheritdoc
     */
    protected function createMigration($class)
    {
        return $this->getMigrator()->createMigration($class);
    }

    /**
     * @inheritdoc
     */
    protected function getNewMigrations()
    {
        return $this->getMigrator()->getNewMigrations();
    }

    /**
     * @inheritdoc
     */
    protected function getMigrationHistory($limit)
    {
        return $this->getMigrator()->getMigrationHistory($limit);
    }

    /**
     * @inheritdoc
     */
    protected function addMigrationHistory($version)
    {
        $this->getMigrator()->addMigrationHistory($version);
    }

    /**
     * @inheritdoc
     */
    protected function removeMigrationHistory($version)
    {
        $this->getMigrator()->removeMigrationHistory($version);
    }
}
