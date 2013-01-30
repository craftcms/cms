<?php
namespace Blocks;

/**
 *
 */
class DbMigration extends \CDbMigration
{

	/**
	 * This method contains the logic to be executed when applying this migration.
	 * Child classes may implement this method to provide actual migration logic.
	 *
	 * @return boolean
	 */
	public function up()
	{
		$transaction = $this->getDbConnection()->beginTransaction();

		try
		{
			ob_start();
			$result = $this->safeUp();
			$output = ob_get_clean();

			Blocks::log($output, \CLogger::LEVEL_INFO);

			if ($result === false)
			{
				$transaction->rollback();
				return false;
			}

			$transaction->commit();
			return true;
		}
		catch(\Exception $e)
		{
			$output = ob_get_clean();
			Blocks::log($output, \CLogger::LEVEL_ERROR);

			Blocks::log($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')', \CLogger::LEVEL_ERROR);
			Blocks::log($e->getTraceAsString(), \CLogger::LEVEL_ERROR);

			$transaction->rollback();
			return false;
		}
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @param null  $options
	 * @param bool  $addIdColumn
	 * @param bool  $addAuditColumns
	 * @return int
	 */
	public function createTable($table, $columns, $options = null, $addIdColumn = true, $addAuditColumns = true)
	{
		echo "    > create table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->createTable($table, $columns, $options, $addIdColumn, $addAuditColumns);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		echo "    > batch-insert into $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->insertAll($table, $columns, $vals);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTableIfExists($table)
	{
		echo "    > drop table if exists $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropTableIfExists($table);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumn($table, $column, $type)
	{
		echo "    > add column $column to table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addColumn($table, $column, $type);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		echo "    > add column $column first to table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addColumnFirst($table, $column, $type);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 * @return mixed
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		echo "    > add column $column before $before to table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addColumnBefore($table, $column, $type, $before);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		echo "    > add column $column after $after to table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addColumnAfter($table, $column, $type, $after);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param      $after
	 * @return int
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		echo "    > alter column $column in table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->alterColumn($table, $column, $type, $newName, $after);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $refTable
	 * @param $refColumns
	 * @param null $delete
	 * @param null $update
	 * @return int
	 */
	public function addForeignKey($table, $columns, $refTable, $refColumns, $delete = null, $update = null)
	{
		echo "    > add foreign key to $table ($columns) references $refTable ($refColumns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addForeignKey($table, $columns, $refTable, $refColumns, $delete, $update);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropForeignKey($table, $columns)
	{
		echo "    > drop foreign key from table $table ($columns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropForeignKey($table, $columns);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param bool $unique
	 * @return int
	 */
	public function createIndex($table, $columns, $unique = false)
	{
		echo "    > create".($unique ? ' unique':'')." index on $table ($columns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->createIndex($table, $columns, $unique);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * $param $unique
	 * @param bool   $unique
	 * @return int
	 */
	public function dropIndex($table, $columns, $unique = false)
	{
		echo "    > drop".($unique ? ' unique':'')." index on $table ($columns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropIndex($table, $columns, $unique);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function addPrimaryKey($table, $columns)
	{
		echo "    > alter table $table add new primary key ($columns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addPrimaryKey($table, $columns);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropPrimaryKey($table, $columns)
	{
		echo "    > alter table $table drop primary key ($columns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropPrimaryKey($table, $columns);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}
}
