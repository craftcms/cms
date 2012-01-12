<?php

/**
 *
 */
class DumpSchemaCommand extends CConsoleCommand
{
	/**
	 * @access public
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function run($args)
	{
		if (count($args) == 0)
		{
			echo 'You must specify a schema name: yiic dumpschema <schema name>';
			return;
		}

		$schema = $args[0];
		$tables = Yii::app()->db->schema->getTables($schema);
		$result = '';

		foreach ($tables as $def)
		{
			$result .= '$this->createTable("' . $def->name . '", array(' . "\n";

			foreach ($def->columns as $col)
				$result .= '    "' . $col->name . '"=>"' . $this->getColType($col) . '",' . "\n";

			$result .= '), "");' . "\n\n";
		}

		echo $result;
	}

	/**
	 * @access public
	 *
	 * @param $col
	 *
	 * @return string
	 */
	public function getColType($col)
	{
		if ($col->isPrimaryKey)
			return "pk";

		$result = $col->dbType;

		if (!$col->allowNull)
			$result .= ' NOT NULL';

		if ($col->defaultValue != null)
			$result .= " DEFAULT '{$col->defaultValue}'";

		return $result;
	}
}
