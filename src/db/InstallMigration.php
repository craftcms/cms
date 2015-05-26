<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use craft\app\helpers\MigrationHelper;

/**
 * InstallMigration is the base class for installation migration classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class InstallMigration extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$indexes = [];
		$foreignKeys = [];

		foreach ($this->defineSchema() as $tableName => $table)
		{
			$this->createTable(
				$tableName,
				$table['columns'],
				isset($table['options']) ? $table['options'] : null,
				isset($table['addIdColumn']) ? $table['addIdColumn'] : true,
				isset($table['addAuditColumns']) ? $table['addAuditColumns'] : true
			);

			if (isset($table['indexes']))
			{
				foreach ($table['indexes'] as $index)
				{
					$required = isset($index[1]) ? $index[1] : false;
					$indexes[] = [
						$this->db->getIndexName($tableName, $index[0], $required),
						$tableName,
						$index[0],
						$required
					];
				}
			}

			// Check if there's an explicit primary key after we've added the indexes to avoid creating an unnecessary PK index
			if (isset($table['primaryKey']))
			{
				$this->addPrimaryKey(
					$this->db->getPrimaryKeyName($tableName, $table['primaryKey']),
					$tableName,
					$table['primaryKey']
				);
			}

			if (isset($table['foreignKeys']))
			{
				foreach ($table['foreignKeys'] as $foreignKey)
				{
					$foreignKeys[] = [
						$this->db->getForeignKeyName($tableName, $foreignKey[0]),
						$tableName,
						$foreignKey[0],
						$foreignKey[1],
						$foreignKey[2],
						isset($foreignKey[3]) ? $foreignKey[3] : null,
						isset($foreignKey[4]) ? $foreignKey[4] : null
					];
				}
			}
		}

		foreach ($indexes as $index)
		{
			$this->createIndex($index[0], $index[1], $index[2], $index[3]);
		}

		foreach ($foreignKeys as $foreignKey)
		{
			$this->addForeignKey($foreignKey[0], $foreignKey[1], $foreignKey[2], $foreignKey[3], $foreignKey[4], $foreignKey[5], $foreignKey[6]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$tableNames = array_keys($this->defineSchema());

		foreach ($tableNames as $tableName)
		{
			MigrationHelper::dropTable($tableName, $this);
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns info about the tables that should be created by this migration.
	 *
	 * The array keys should be the table names (either their full names or in the `{{%shortname}}` format without a prefix),
	 * and the values should be sub-arrays with the following keys:
	 *
	 * - 'columns' (array)           - The columns (name => definition) in the new table
	 * - 'options' (string)          - Additional SQL fragment that will be appended to the generated SQL (optional, default is null)
	 * - 'addIdColumn' (boolean)     - Whether an `id` column should be added (optional, default is true)
	 * - 'addAuditColumns' (boolean) - Whether `dateCreated` and `dateUpdated` columns should be added (optional, default is true)
	 * - 'indexes' (array)           - The indexes ([columns, unique]) the table should have (optional)
	 * - 'foreignKeys' (array)       - The foreign keys ([columns, refTable, refColumns, delete, update]) the table should have (optional)
	 *
	 * @return array Info about the tables that should be created by this migration
	 */
	abstract protected function defineSchema();
}
