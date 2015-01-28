<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use Craft;
use yii\base\NotSupportedException;

/**
 * @inheritDoc \yii\db\Migration
 *
 * @property Connection $db Connection the DB connection that this command is associated with.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Migration extends \yii\db\Migration
{
	// Public Methods
	// =========================================================================

	// Migration Actions
	// -------------------------------------------------------------------------

	/**
	 * This method contains the logic to be executed when applying this migration. Child classes may implement this
	 * method to provide actual migration logic.
	 *
	 * @return bool
	 */
	public function up()
	{
		$transaction = $this->db->getTransaction() === null ? $this->db->beginTransaction() : null;

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
			Craft::error($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')', __METHOD__);
			Craft::error($e->getTraceAsString(), __METHOD__);

			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			return false;
		}
	}

	/**
	 * @throws NotSupportedException
	 */
	public function down()
	{
		throw new NotSupportedException('"down" is not implemented.');
	}

	/**
	 * @throws NotSupportedException
	 */
	public function safeDown()
	{
		throw new NotSupportedException('"safeDown" is not implemented.');
	}

	// Database Commands
	// -------------------------------------------------------------------------

	/**
	 * @param $table
	 */
	public function dropTableIfExists($table)
	{
		echo "    > dropping $table if it exists ...";
		$time = microtime(true);
		$this->db->createCommand()->dropTableIfExists($table)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 */
	public function addColumnFirst($table, $column, $type)
	{
		echo "    > add column $column $type to first position in table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->addColumnFirst($table, $column, $type)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		echo "    > add column $column $type before $before in table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->addColumnBefore($table, $column, $type, $before)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		echo "    > add column $column $type after $after in $table ...";
		$time = microtime(true);
		$this->db->createCommand()->addColumnAfter($table, $column, $type, $after)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param null $after
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		echo "    > alter column $column $type in table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->alterColumn($table, $column, $type, $newName, $after)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Creates and executes an INSERT SQL statement. The method will properly escape the column names, and bind the
	 * values to be inserted.
	 *
	 * @param string $table               The table that new rows will be inserted into.
	 * @param array  $columns             The column data (name=>value) to be inserted into the table.
	 * @param bool   $includeAuditColumns Whether to include the data for the audit columns
	 *                                    (dateCreated, dateUpdated, uid).
	 */
	public function insert($table, $columns, $includeAuditColumns = true)
	{
		echo "    > insert into $table ...";
		$time = microtime(true);
		$this->db->createCommand()->insert($table, $columns, $includeAuditColumns)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Creates and executes an UPDATE SQL statement. The method will properly escape the column names and bind the
	 * values to be updated.
	 *
	 * @param string $table               The table to be updated.
	 * @param array  $columns             The column data (name=>value) to be updated.
	 * @param mixed  $conditions          The conditions that will be put in the WHERE part. Please refer to
	 *                                    [[\CDbCommand::where]] on how to specify conditions.
	 * @param array  $params              The parameters to be bound to the query.
	 * @param bool   $includeAuditColumns Whether to include the data for the audit columns (dateCreated, dateUpdated, uid).
	 */
	public function update($table, $columns, $conditions = '', $params = [], $includeAuditColumns = true)
	{
		echo "    > update in $table ...";
		$time = microtime(true);
		$this->db->createCommand()->update($table, $columns, $conditions, $params, $includeAuditColumns)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * @param string $table
	 * @param array  $keyColumns
	 * @param array  $updateColumns
	 * @param bool   $includeAuditColumns
	 */
	public function insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns = true)
	{
		echo "    > insert or update in $table ...";
		$time = microtime(true);
		$this->db->createCommand()->insertOrUpdate($table, $keyColumns, $updateColumns, $includeAuditColumns)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Creates and executes a DELETE SQL statement.
	 *
	 * @param string $table      The table where the data will be deleted from.
	 * @param mixed  $conditions The conditions that will be put in the WHERE part. Please refer to
	 *                           [[\CDbCommand::where]] on how to specify conditions.
	 * @param array  $params     The parameters to be bound to the query.
	 */
	public function delete($table, $conditions = '', $params = [])
	{
		echo "    > delete from $table ...";
		$time = microtime(true);
		$this->db->createCommand()->delete($table, $conditions, $params)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Builds and executes a SQL statement for renaming a DB table.
	 *
	 * @param string $table   The table to be renamed. The name will be properly quoted by the method.
	 * @param string $newName The new table name. The name will be properly quoted by the method.
	 */
	public function renameTable($table, $newName)
	{
		echo "    > rename table $table to $newName ...";
		$time = microtime(true);
		$this->db->createCommand()->renameTable($table, $newName)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB table.
	 *
	 * @param string $table The table to be dropped. The name will be properly quoted by the method.
	 */
	public function dropTable($table)
	{
		echo "    > drop table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->dropTable($table)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Builds and executes a SQL statement for truncating a DB table.
	 *
	 * @param string $table The table to be truncated. The name will be properly quoted by the method.
	 */
	public function truncateTable($table)
	{
		echo "    > truncate table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->truncateTable($table)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB column.
	 *
	 * @param string $table  The table whose column is to be dropped. The name will be properly quoted by the method.
	 * @param string $column The name of the column to be dropped. The name will be properly quoted by the method.
	 */
	public function dropColumn($table, $column)
	{
		echo "    > drop column $column from table $table ...";
		$time = microtime(true);
		$this->db->createCommand()->dropColumn($table, $column)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Builds and executes a SQL statement for renaming a column.
	 *
	 * @param string $table   The table whose column is to be renamed. The name will be properly quoted by the method.
	 * @param string $name    The old name of the column. The name will be properly quoted by the method.
	 * @param string $newName The new name of the column. The name will be properly quoted by the method.
	 */
	public function renameColumn($table, $name, $newName)
	{
		echo "    > rename column $name in table $table to $newName ...";
		$time = microtime(true);
		$this->db->createCommand()->renameColumn($table, $name, $newName)->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}

	/**
	 * Refreshed schema cache for a table
	 *
	 * @param string $table The name of the table to refresh
	 */
	public function refreshTableSchema($table)
	{
		echo "    > refresh table $table schema cache ...";
		$time = microtime(true);
		$this->db->getSchema()->getTableSchema($table, true);
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}
}
