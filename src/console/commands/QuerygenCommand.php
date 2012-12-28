<?php
namespace Blocks;

/**
 * Query generator console command
 */
class QuerygenCommand extends \CConsoleCommand
{
	/**
	 * Returns the PHP code to create a new table from a given record.
	 */
	public function actionCreateTableForRecord($args)
	{
		$record = $this->_getRecord($args[0]);

		$table = $record->getTableName();
		$indexes = $record->defineIndexes();
		$columns = array();

		// Add any Foreign Key columns
		foreach ($record->getBelongsToRelations() as $name => $config)
		{
			$required = !empty($config['required']);
			$columns[$config[2]] = array('column' => ColumnType::Int, 'required' => $required);

			// Add unique index for this column?
			// (foreign keys already get indexed, so we're only concerned with whether it should be unique)
			if (!empty($config['unique']))
			{
				$indexes[] = array('columns' => array($config[2]), 'unique' => true);
			}
		}

		// Add all other columns
		foreach ($record->defineAttributes() as $name => $config)
		{
			$config = ModelHelper::normalizeAttributeConfig($config);

			// Add (unique) index for this column?
			$indexed = !empty($config['indexed']);
			$unique = !empty($config['unique']);

			if ($unique || $indexed)
			{
				$indexes[] = array('columns' => array($name), 'unique' => $unique);
			}

			$columns[$name] = $config;
		}

		// Create the table
		echo "\n// Create the {$table} table\n";

		echo "blx()->db->createCommand()->createTable('{$table}', array(\n"; //.var_export($columns, true).");\n";

		foreach ($columns as $name => $config)
		{
			echo "\t'{$name}' => ".implode(' ', array_map('trim', explode("\n", var_export($config, true)))).",\n";
		}

		echo "));\n";

		// Create the indexes
		if ($indexes)
		{
			echo "\n// Add the indexes\n";
			foreach ($indexes as $index)
			{
				$columns = ArrayHelper::stringToArray($index['columns']);
				$unique = !empty($index['unique']);
				$name = "{$table}_".implode('_', $columns).($unique ? '_unique' : '').'_idx';

				echo "blx()->db->createCommand()->createIndex('{$name}', '{$table}', '".implode("','", $columns)."', ".($unique ? 'true' : 'false').");\n";
			}
		}

		echo "\n";

		return 1;
	}

	/**
	 * Returns a record instance by its class name.
	 *
	 * @access private
	 * @param string $class
	 * @return BaseRecord
	 */
	private function _getRecord($class)
	{
		$nsClass = __NAMESPACE__.'\\'.$class;

		if (!class_exists($nsClass))
		{
			echo 'Error: No records exist with the class '.$nsClass."\n";
			exit(1);
		}

		return new $nsClass('install');
	}
}
