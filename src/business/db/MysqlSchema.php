<?php
namespace Blocks;

/**
 *
 */
class MysqlSchema extends \CMysqlSchema
{
	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 * @return string
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		$type = $this->getColumnType($type);

		$sql = 'ALTER TABLE '.$this->quoteTableName($table)
		       .' ADD '.$this->quoteColumnName($column).' '
		       .$this->getColumnType($type).' '
		       .'AFTER '.$this->quoteTableName($after);

		return $sql;
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return mixed
	 */
	public function insertAll($table, $columns, $vals)
	{
		$params = array();
		$names = array();
		$placeHolders = array();

		// parameterize the parameters
		$uniqueCounter = 0;
		foreach ($vals as $val)
		{
			for ($columnCounter = 0; $columnCounter < count($columns); $columnCounter++)
			{
				$placeHolders[] = ':'.$columns[$columnCounter].($uniqueCounter + 1);
				$params[':' . $columns[$columnCounter].($uniqueCounter + 1)] = $val[$columnCounter];

			}

			$uniqueCounter++;
		}

		foreach ($columns as $columnName)
			$names[] = $this->quoteColumnName($columnName);

		// generate the SQL
		$sql='INSERT INTO ' . $this->quoteTableName($table)
			. ' (' . implode(', ',$names) . ') VALUES (';

		$columnCounter = 0;
		foreach ($placeHolders as $placeHolder)
		{
			if ($columnCounter == count($names))
			{
				$columnCounter = 0;
				$sql = rtrim($sql, ',');
				$sql .= '), (';
			}

			$sql .= $placeHolder.',';
			$columnCounter++;
		}

		$sql = rtrim($sql, ',');
		$sql .= ')';

		return array('query' => $sql, 'params' => $params);
	}
}
