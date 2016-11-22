<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db\pgsql;

use Craft;
use craft\services\Config;
use yii\db\Exception;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Schema extends \yii\db\pgsql\Schema
{
    // Properties
    // =========================================================================

    /**
     * @var int The maximum length that objects' names can be.
     */
    public $maxObjectNameLength = 63;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->defaultSchema = Craft::$app->getConfig()->get('schema', Config::CATEGORY_DB);
    }

    /**
     * Creates a query builder for the database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     *
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * Quotes a database name for use in a query.
     *
     * @param $name
     *
     * @return string
     */
    public function quoteDatabaseName($name)
    {
        return '"'.$name.'"';
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     */

    public function releaseSavepoint($name)
    {
        try {
            parent::releaseSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "No such savepoint" error.
            if ($e->getCode() == 3 && isset($e->errorInfo[0]) && isset($e->errorInfo[1]) && $e->errorInfo[0] == '3B001' && $e->errorInfo[1] == 7) {
                Craft::warning('Tried to release a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     */
    public function rollBackSavepoint($name)
    {
        try {
            parent::rollBackSavepoint($name);
        } catch (Exception $e) {
            // Specifically look for a "No such savepoint" error.
            if ($e->getCode() == 3 && isset($e->errorInfo[0]) && isset($e->errorInfo[1]) && $e->errorInfo[0] == '3B001' && $e->errorInfo[1] == 7) {
                Craft::warning('Tried to roll back a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($sequenceName !== '') {
            $sequenceName = $this->defaultSchema.'.'.$this->getRawTableName($sequenceName).'_id_seq';
        }

        return parent::getLastInsertID($sequenceName);
    }

    /**
     * Returns the default backup command to execute.
     *
     * @return string|false The command to execute
     */
    public function getDefaultBackupCommand()
    {
        return 'pg_dump'.
            ' --dbname={database}'.
            ' --host={server}'.
            ' --port={port}'.
            ' --username={user}'.
            ' --no-password'.
            ' --if-exists'.
            ' --clean'.
            ' --file={file}'.
            ' --schema={schema}';
    }

    /**
     * Returns the default database restore command to execute.
     *
     * @return string The command to execute
     */
    public function getDefaultRestoreCommand()
    {
        return 'psql'.
            ' --dbname={database}'.
            ' --host={server}'.
            ' --port={port}'.
            ' --username={user}'.
            ' --no-password'.
            ' < {file}';
    }
}
