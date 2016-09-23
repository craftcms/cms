<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db\mysql;

use Craft;
use yii\db\Exception;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Schema extends \yii\db\mysql\Schema
{
    // Constants
    // =========================================================================

    const TYPE_TINYTEXT = 'tinytext';
    const TYPE_MEDIUMTEXT = 'mediumtext';
    const TYPE_LONGTEXT = 'longtext';
    const TYPE_ENUM = 'enum';

    // Properties
    // =========================================================================

    /**
     * @var int The maximum length that objects' names can be.
     */
    public $maxObjectNameLength = 64;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->typeMap['tinytext'] = self::TYPE_TINYTEXT;
        $this->typeMap['mediumtext'] = self::TYPE_MEDIUMTEXT;
        $this->typeMap['longtext'] = self::TYPE_LONGTEXT;
        $this->typeMap['enum'] = self::TYPE_ENUM;
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
        return '`'.$name.'`';
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
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
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
            // Specifically look for a "SAVEPOINT does not exist" error.
            if ($e->getCode() == 42000 && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1305) {
                Craft::warning('Tried to roll back a savepoint, but it does not exist: '.$e->getMessage(), __METHOD__);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->db);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns all table names in the database which start with the tablePrefix.
     *
     * @param string $schema
     *
     * @return string
     */
    protected function findTableNames($schema = null)
    {
        if (!$schema) {
            $likeSql = ($this->db->tablePrefix ? ' LIKE \''.$this->db->tablePrefix.'%\'' : '');

            return $this->db->createCommand('SHOW TABLES'.$likeSql)->queryColumn();
        }

        return parent::findTableNames();
    }
}
