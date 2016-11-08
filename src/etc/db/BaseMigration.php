<?php
namespace Craft;

/**
 * Class BaseMigration
 *
 * @property DbConnection $dbConnection The currently active database connection.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.db
 * @since     1.0
 */
abstract class BaseMigration extends \CDbMigration
{
	// Public Methods
	// =========================================================================

	/**
	 * This method contains the logic to be executed when applying this migration. Child classes may implement this
	 * method to provide actual migration logic.
	 *
	 * @return bool
	 */
	public function up()
	{
		$transaction = $this->dbConnection->getCurrentTransaction() === null ? $this->dbConnection->beginTransaction() : null;

		try
		{
			$result = $this->safeUp();

			if ($result === false)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				return false;
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return true;
		}
		catch(\Exception $e)
		{
			Craft::log($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')', LogLevel::Error);
			Craft::log($e->getTraceAsString(), LogLevel::Error);

			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			return false;
		}
	}

	/**
	 * Builds and executes a SQL statement for creating a new DB table.
	 *
	 * The columns in the new  table should be specified as name-definition pairs (e.g. 'name'=>'string'), where name
	 * stands for a column name which will be properly quoted by the method, and definition stands for the column type
	 * which can contain an abstract DB type. The {@link getColumnType} method will be invoked to convert any abstract
	 * type into a physical one.
	 *
	 * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly inserted
	 * into the generated SQL.
	 *
	 * @param string $table           The name of the table to be created. The name will be properly quoted by the method.
	 * @param array  $columns         The columns (name=>definition) in the new table.
	 * @param string $options         Additional SQL fragment that will be appended to the generated SQL.
	 * @param bool   $addIdColumn     Whether to add an auto-incrementing primary key id column to the table.
	 * @param bool   $addAuditColumns Whether to append auditing columns to the end of the table (dateCreated,
	 *                                dateUpdated, uid)
	 *
	 * @return null
	 */
	public function createTable($table, $columns, $options = null, $addIdColumn = true, $addAuditColumns = true)
	{
		Craft::log('Create table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->createTable($table, $columns, $options, $addIdColumn, $addAuditColumns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @param bool
	 *
	 * @return null
	 */
	public function insertAll($table, $columns, $vals, $includeAuditColumns = true)
	{
		Craft::log('Batch inserting into '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->insertAll($table, $columns, $vals, $includeAuditColumns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param $table
	 *
	 * @return null
	 */
	public function dropTableIfExists($table)
	{
		Craft::log('Dropping table if exists '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropTableIfExists($table);
		$this->_processDoneTime($time);
		return $return;
	}

	/**
	 * Builds and executes a SQL statement for adding a new DB column.
	 *
	 * @param string $table  The table that the new column will be added to. The table name will be properly quoted by
	 *                       the method.
	 * @param string $column The name of the new column. The name will be properly quoted by the method.
	 * @param string $type   The column type. The {@link getColumnType} method will be invoked to convert abstract
	 *                       column type (if any) into the physical one. Anything that is not recognized as abstract
	 *                       type will be kept in the generated SQL. For example, 'string' will be turned into
	 *                       'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
	 *
	 * @return null
	 */
	public function addColumn($table, $column, $type)
	{
		Craft::log('Adding column '.$column.' to table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addColumn($table, $column, $type);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 *
	 * @return null
	 */
	public function addColumnFirst($table, $column, $type)
	{
		Craft::log('Adding column '.$column.' first to table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addColumnFirst($table, $column, $type);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 *
	 * @return null
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		Craft::log('Adding column '.$column.' before '.$before.' to table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addColumnBefore($table, $column, $type, $before);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 *
	 * @return null
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		Craft::log('Adding column '.$column.' after '.$after.' to table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addColumnAfter($table, $column, $type, $after);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param null $after
	 *
	 * @return null
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		Craft::log('Altering column '.$column.' in table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->alterColumn($table, $column, $type, $newName, $after);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param      $table
	 * @param      $columns
	 * @param      $refTable
	 * @param      $refColumns
	 * @param null $delete
	 * @param null $update
	 *
	 * @return null
	 */
	public function addForeignKey($table, $columns, $refTable, $refColumns, $delete = null, $update = null)
	{
		Craft::log('Adding foreign key to '.$table.' ('.$columns.') references '.$refTable.' ('.$refColumns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addForeignKey($table, $columns, $refTable, $refColumns, $delete, $update);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param string $table
	 * @param string $columns
	 *
	 * @return null
	 */
	public function dropForeignKey($table, $columns)
	{
		Craft::log('Dropping foreign key from table '.$table.' ('.$columns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropForeignKey($table, $columns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param      $table
	 * @param      $columns
	 * @param bool $unique
	 *
	 * @return null
	 */
	public function createIndex($table, $columns, $unique = false)
	{
		Craft::log('Creating '.($unique ? ' unique' : '').' index on '.$table.' ('.$columns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->createIndex($table, $columns, $unique);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @param bool   $unique
	 *
	 * @return null
	 */
	public function dropIndex($table, $columns, $unique = false)
	{
		Craft::log('Dropping '.($unique ? ' unique' : '').' index on '.$table.' ('.$columns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropIndex($table, $columns, $unique);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param string $table
	 * @param string $columns
	 *
	 * @return null
	 */
	public function addPrimaryKey($table, $columns)
	{
		Craft::log('Altering table '.$table.' add new primary key ('.$columns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->addPrimaryKey($table, $columns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param string $table
	 * @param string $columns
	 *
	 * @return null
	 */
	public function dropPrimaryKey($table, $columns)
	{
		Craft::log('Altering table '.$table.' drop primary key ('.$columns.') ...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropPrimaryKey($table, $columns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @return bool|null
	 */
	public function down()
	{
		Craft::log('Down migrations are not supported.', LogLevel::Warning);
	}

	/**
	 * @return bool|null
	 */
	public function safeDown()
	{
		Craft::log('Down migrations are not supported.', LogLevel::Warning);
	}

	/**
	 * Executes a SQL statement. This method executes the specified SQL statement using {@link dbConnection}.
	 *
	 * @param string $sql    The SQL statement to be executed.
	 * @param array  $params Input parameters (name=>value) for the SQL execution. See {@link \CDbCommand::execute} for
	 *                       more details.
	 *
	 * @return null
	 */
	public function execute($sql, $params=array())
	{
		Craft::log('Executing SQL: '.$sql.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand($sql)->execute($params);

		$this->_processDoneTime($time);
	}

	/**
	 * Creates and executes an INSERT SQL statement. The method will properly escape the column names, and bind the
	 * values to be inserted.
	 *
	 * @param string $table               The table that new rows will be inserted into.
	 * @param array  $columns             The column data (name=>value) to be inserted into the table.
	 * @param bool   $includeAuditColumns Whether to include the data for the audit columns
	 *                                    (dateCreated, dateUpdated, uid).
	 *
	 * @return null
	 */
	public function insert($table, $columns, $includeAuditColumns = true)
	{
		Craft::log('Inserting into '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->insert($table, $columns, $includeAuditColumns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Creates and executes an UPDATE SQL statement. The method will properly escape the column names and bind the
	 * values to be updated.
	 *
	 * @param string $table               The table to be updated.
	 * @param array  $columns             The column data (name=>value) to be updated.
	 * @param mixed  $conditions          The conditions that will be put in the WHERE part. Please refer to
	 *                                    {@link \CDbCommand::where} on how to specify conditions.
	 * @param array  $params              The parameters to be bound to the query.
	 * @param bool   $includeAuditColumns Whether to include the data for the audit columns (dateCreated, dateUpdated, uid).
	 *
	 * @return null
	 */
	public function update($table, $columns, $conditions = '', $params = array(), $includeAuditColumns = true)
	{
		Craft::log('Updating '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->update($table, $columns, $conditions, $params, $includeAuditColumns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * @param string $table
	 * @param array  $keyColumns
	 * @param array  $updateColumns
	 * @param bool   $includeAuditColumns
	 *
	 * @return null
	 */
	public function insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns = true)
	{
		Craft::log('Inserting or updating '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Creates and executes a DELETE SQL statement.
	 *
	 * @param string $table      The table where the data will be deleted from.
	 * @param mixed  $conditions The conditions that will be put in the WHERE part. Please refer to
	 *                           {@link \CDbCommand::where} on how to specify conditions.
	 * @param array  $params     The parameters to be bound to the query.
	 *
	 * @return null
	 */
	public function delete($table, $conditions = '', $params = array())
	{
		Craft::log('Deleting from '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->delete($table, $conditions, $params);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Builds and executes a SQL statement for renaming a DB table.
	 *
	 * @param string $table   The table to be renamed. The name will be properly quoted by the method.
	 * @param string $newName The new table name. The name will be properly quoted by the method.
	 *
	 * @return null
	 */
	public function renameTable($table, $newName)
	{
		Craft::log('Renaming table '.$table.' to '.$newName.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->renameTable($table, $newName);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB table.
	 *
	 * @param string $table The table to be dropped. The name will be properly quoted by the method.
	 *
	 * @return null
	 */
	public function dropTable($table)
	{
		Craft::log('Dropping table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropTable($table);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Builds and executes a SQL statement for truncating a DB table.
	 *
	 * @param string $table The table to be truncated. The name will be properly quoted by the method.
	 *
	 * @return null
	 */
	public function truncateTable($table)
	{
		Craft::log('Truncating table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->truncateTable($table);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB column.
	 *
	 * @param string $table  The table whose column is to be dropped. The name will be properly quoted by the method.
	 * @param string $column The name of the column to be dropped. The name will be properly quoted by the method.
	 *
	 * @return null
	 */
	public function dropColumn($table, $column)
	{
		Craft::log('Drop column '.$column.' from table '.$table.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->dropColumn($table, $column);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Builds and executes a SQL statement for renaming a column.
	 *
	 * @param string $table   The table whose column is to be renamed. The name will be properly quoted by the method.
	 * @param string $name    The old name of the column. The name will be properly quoted by the method.
	 * @param string $newName The new name of the column. The name will be properly quoted by the method.
	 *
	 * @return null
	 */
	public function renameColumn($table, $name, $newName)
	{
		Craft::log('Rename column '.$name.' in table '.$table.' to '.$newName.'...');
		$time = microtime(true);
		$return = $this->dbConnection->createCommand()->renameColumn($table, $name, $newName);
		$this->_processDoneTime($time);

		return $return;
	}

	/**
	 * Refreshed schema cache for a table
	 *
	 * @param string $table The name of the table to refresh
	 *
	 * @return null
	 */
	public function refreshTableSchema($table)
	{
		Craft::log('Refresh table '.$table.' schema cache...');

		$time = microtime(true);
		$this->dbConnection->getSchema()->getTable($table,true);

		$this->_processDoneTime($time);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $time
	 *
	 * @return null
	 */
	private function _processDoneTime($time)
	{
		Craft::log("Done (time: ".sprintf('%.3f', microtime(true) - $time)."s)");
	}
}
