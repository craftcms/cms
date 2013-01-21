<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121230_230206_new_index_names extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tablePrefixLength = strlen(blx()->db->tablePrefix);
		$tables = DbHelper::getTableNames();

		foreach ($tables as $prefixedTable)
		{
			// Get the CREATE TABLE sql
			$query = blx()->db->createCommand()
				->setText("SHOW CREATE TABLE `{$prefixedTable}`")
				->queryRow();

			$createTableSql = $query['Create Table'];

			// Get the table name without the table prefix
			$table = substr($prefixedTable, $tablePrefixLength);

			$foreignKeys = array();

			// Drop the foreign keys
			//-----------------------

			if (preg_match_all('/CONSTRAINT `(\w+)` FOREIGN KEY \(`([\w`,]+)`\) REFERENCES `(\w+)` \(`([\w`,]+)`\)( ON DELETE ([A-Z]+))?( ON UPDATE ([A-Z]+))?/', $createTableSql, $matches, PREG_SET_ORDER))
			{
				$newForeignKeyNames = array();

				foreach ($matches as $match)
				{
					$oldName = $match[1];
					$columns = explode('`,`', $match[2]);
					$refTable = substr($match[3], $tablePrefixLength);
					$refColumns = explode('`,`', $match[4]);
					$onDelete = (!empty($match[6]) ? $match[6] : null);
					$onUpdate = (!empty($match[8]) ? $match[8] : null);

					// Drop the old foreign key
					$this->_dropForeignKey($oldName, $prefixedTable);

					// Queue up the new one
					$newName = DbHelper::getForeignKeyName($table, $columns);

					if (!in_array($newName, $newForeignKeyNames))
					{
						$foreignKeys[$oldName] = (object) array(
							'name' => $newName,
							'table' => $table,
							'columns' => implode(',', $columns),
							'refTable' => $refTable,
							'refColumns' => implode(',', $refColumns),
							'onDelete' => $onDelete,
							'onUpdate' => $onUpdate
						);

						$newForeignKeyNames[] = $newName;
					}
				}
			}

			// Rename the indexes
			//--------------------

			if (preg_match_all('/(UNIQUE )?KEY `(\w+)` \(`([\w`,]+)`\)/', $createTableSql, $matches, PREG_SET_ORDER))
			{
				$newIndexNames = array();

				foreach ($matches as $match)
				{
					$unique = !empty($match[1]);
					$oldName = $match[2];
					$columns = explode('`,`', $match[3]);

					// Drop the old index
					$this->_dropIndex($oldName, $prefixedTable);

					// Add the new one, unless it was created for a FK
					if (!isset($foreignKeys[$oldName]))
					{
						$newName = DbHelper::getIndexName($table, $columns, $unique);

						if (!in_array($newName, $newIndexNames))
						{
							blx()->db->createCommand()->createIndex($table, implode(',', $columns), $unique);

							$newIndexNames[] = $newName;
						}
					}
				}
			}

			// Add the new foreign keys
			//--------------------------

			foreach ($foreignKeys as $fk)
			{
				blx()->db->createCommand()->addForeignKey($fk->table, $fk->columns, $fk->refTable, $fk->refColumns, $fk->onDelete, $fk->onUpdate);
			}
		}

		return true;
	}

	/**
	 * Drops a foreign key without running the name through DbHelper::normalizeIndexName.
	 *
	 * @access private
	 * @param string $name
	 * @param string $table
	 * @return int
	 */
	private function _dropForeignKey($name, $table)
	{
		return blx()->db->createCommand()
			->setText(blx()->db->getSchema()->dropForeignKey($name, $table))
			->execute();
	}

	/**
	 * Drops an index without running the name through DbHelper::normalizeIndexName.
	 *
	 * @access private
	 * @param string $name
	 * @param string $table
	 * @return int
	 */
	private function _dropIndex($name, $table)
	{
		return blx()->db->createCommand()
			->setText(blx()->db->getSchema()->dropIndex($name, $table))
			->execute();
	}
}
