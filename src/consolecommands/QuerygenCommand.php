<?php
namespace Craft;

/**
 * The query generator console command.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.consolecommands
 * @since     1.0
 */
class QuerygenCommand extends BaseCommand
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $defaultAction = 'all';

	// Public Methods
	// =========================================================================

	/**
	 * @param $args
	 */
	public function actionAll($args)
	{
		$this->actionCreateTableForRecord($args);
		$this->actionAddForeignKeysForRecord($args);
	}

	/**
	 * Returns the PHP code to create a new table from a given record.
	 *
	 * @param $args
	 *
	 * @return int
	 */
	public function actionCreateTableForRecord($args)
	{
		$record = $this->_getRecord($args[0]);

		$table = $record->getTableName();
		$indexes = $record->defineIndexes();
		$attributes = $record->getAttributeConfigs();
		$columns = array();

		// Add any Foreign Key columns
		foreach ($record->getBelongsToRelations() as $name => $config)
		{
			$columnName = $config[2];

			// Is the record already defining this column?
			if (isset($attributes[$columnName]))
			{
				continue;
			}

			$required = !empty($config['required']);
			$columns[$columnName] = array('column' => ColumnType::Int, 'required' => $required);

			// Add unique index for this column? (foreign keys already get indexed, so we're only concerned with whether
			// it should be unique)
			if (!empty($config['unique']))
			{
				$indexes[] = array('columns' => array($columnName), 'unique' => true);
			}
		}

		// Add all other columns
		$dbConfigSettings = array('column', 'maxLength', 'length', 'decimals', 'values', 'unsigned', 'zerofill', 'required', 'null', 'default', 'primaryKey');

		foreach ($attributes as $name => $config)
		{
			// Add (unique) index for this column?
			$indexed = !empty($config['indexed']);
			$unique = !empty($config['unique']);

			if ($unique || $indexed)
			{
				$indexes[] = array('columns' => array($name), 'unique' => $unique);
			}

			// Filter out any settings that don't influence the table SQL
			$settings = array_keys($config);

			foreach ($settings as $setting)
			{
				if (!in_array($setting, $dbConfigSettings))
				{
					unset($config[$setting]);
				}
			}

			$columns[$name] = $config;
		}

		$pk = $record->primaryKey();

		if (isset($columns[$pk]))
		{
			$columns[$pk]['primaryKey'] = true;
			$addIdColumn = false;
		}
		else
		{
			$addIdColumn = true;
		}

		// Create the table
		echo "\n// Create the craft_{$table} table\n";

		echo 'craft()->db->createCommand()->createTable(' .
			$this->_varExport($table).", array(\n";

		$colNameLength = max(array_map('strlen', array_keys($columns))) + 2;

		foreach ($columns as $name => $config)
		{
			echo "\t".str_pad("'{$name}'", $colNameLength).' => '.$this->_varExport($config).",\n";
		}

		echo '), null, '.$this->_varExport($addIdColumn).");\n";

		// Create the indexes
		if ($indexes)
		{
			echo "\n// Add indexes to craft_{$table}\n";
			foreach ($indexes as $index)
			{
				$columns = ArrayHelper::stringToArray($index['columns']);
				$unique = !empty($index['unique']);

				echo 'craft()->db->createCommand()->createIndex(' .
					$this->_varExport($table).', ' .
					"'".implode(',', $columns)."', " .
					$this->_varExport($unique).");\n";
			}
		}

		return 1;
	}

	/**
	 * Returns the PHP code to add foreign keys to a table for a given record.
	 *
	 * @param $args
	 *
	 * @return int
	 */
	public function actionAddForeignKeysForRecord($args)
	{
		$record = $this->_getRecord($args[0]);
		$belongsToRelations = $record->getBelongsToRelations();

		if ($belongsToRelations)
		{
			$table = $record->getTableName();

			echo "\n// Add foreign keys to craft_{$table}\n";

			foreach ($belongsToRelations as $name => $config)
			{
				$otherModelClass = $config[1];
				$otherModel = new $otherModelClass('install');
				$otherTable = $otherModel->getTableName();
				$otherPk = $otherModel->primaryKey();

				if (isset($config['onDelete']))
				{
					$onDelete = $config['onDelete'];
				}
				else
				{
					if (empty($config['required']))
					{
						$onDelete = BaseRecord::SET_NULL;
					}
					else
					{
						$onDelete = null;
					}
				}

				if (isset($config['onUpdate']))
				{
					$onUpdate = $config['onUpdate'];
				}
				else
				{
					$onUpdate = null;
				}

				echo 'craft()->db->createCommand()->addForeignKey(' .
					$this->_varExport($table).', ' .
					$this->_varExport($config[2]).', ' .
					$this->_varExport($otherTable).', ' .
					$this->_varExport($otherPk).', ' .
					$this->_varExport($onDelete).', ' .
					$this->_varExport($onUpdate).");\n";
			}
		}

		return 1;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a record instance by its class name.
	 *
	 * @param string $class
	 *
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

	/**
	 * A nicer version of var_export().
	 *
	 * @param mixed $var
	 *
	 * @return string
	 */
	private function _varExport($var)
	{
		if (is_array($var))
		{
			$return = 'array(';

			$count = 0;
			$showingKeys = false;

			foreach ($var as $key => $value)
			{
				if ($count != 0)
				{
					$return .= ', ';
				}

				if (!$showingKeys && $key !== $count)
				{
					$showingKeys = true;
				}

				if ($showingKeys)
				{
					$return .= $this->_varExport($key).' => ';
				}

				$return .= $this->_varExport($value);

				$count++;
			}

			$return .= ')';
		}
		else if (is_bool($var))
		{
			$return = $var ? 'true' : 'false';
		}
		else if (is_null($var))
		{
			$return = 'null';
		}
		else
		{
			$return = var_export($var, true);
		}

		return $return;
	}
}
