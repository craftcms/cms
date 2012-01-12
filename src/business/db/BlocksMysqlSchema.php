<?php

/**
 *
 */
class BlocksMysqlSchema extends CMysqlSchema
{
	/**
	 * @access public
	 *
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 *
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
}
