<?php
namespace Craft;

/**
 *
 */
class DbHelper
{
	/**
	 * Default column configs
	 *
	 * @static
	 * @var array
	 */
	public static $columnTypeDefaults = array(
		ColumnType::Char         => array('maxLength' => 255),
		ColumnType::Varchar      => array('maxLength' => 255),
		ColumnType::TinyInt      => array('maxLength' => 4),
		ColumnType::SmallInt     => array('maxLength' => 6),
		ColumnType::MediumInt    => array('maxLength' => 9),
		ColumnType::Int          => array('maxLength' => 11),
		ColumnType::BigInt       => array('maxLength' => 20),
		ColumnType::Decimal      => array('maxLength' => 10, 'decimals' => 2),
		ColumnType::Enum         => array('values' => array()),
	);

	/**
	 * Numeric column types
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	private static $_numericColumnTypes = array(ColumnType::TinyInt, ColumnType::SmallInt, ColumnType::MediumInt, ColumnType::Int, ColumnType::BigInt, ColumnType::Decimal);

	/**
	 * Textual column types
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	private static $_textualColumnTypes = array(ColumnType::Char, ColumnType::Varchar, ColumnType::TinyText, ColumnType::Text, ColumnType::MediumText, ColumnType::LongText);
	/**
	 * Normalizes a column's config.
	 *
	 * Columns can be defined in 3 ways:
	 *
	 * 1. ColumnType::TypeName
	 * 2. array(ColumnType::TypeName [, 'other' => 'settings' ... ] )
	 * 3. array('column' => ColumnType::TypeName [, 'other' => 'settings' ... ] )
	 *
	 * This function normalizes on the 3rd, merges in the default config settings for the column type,
	 * and renames 'maxLength' to 'length'
	 *
	 * @param string|array $config
	 * @return array
	 */
	public static function normalizeAttributeConfig($config)
	{
		if (is_string($config))
		{
			$config = array('column' => $config);
		}
		else if (!isset($config['column']))
		{
			if (isset($config[0]))
			{
				$config['column'] = $config[0];
				unset($config[0]);
			}
			else
			{
				$config['column'] = ColumnType::Varchar;
			}
		}

		// Merge in the default config
		if ($config['column'] == ColumnType::Locale)
		{
			$config['column'] = ColumnType::Char;
			$config['maxLength'] = 12;
			$config['charset'] = craft()->config->getDbItem('charset');
			$config['collation'] = craft()->config->getDbItem('collation');
		}
		else if (isset(static::$columnTypeDefaults[$config['column']]))
		{
			$config = array_merge(static::$columnTypeDefaults[$config['column']], $config);
		}

		// Rename 'maxLength' to 'length'
		if (isset($config['maxLength']))
		{
			if (!isset($config['length']) && is_numeric($config['maxLength']) && $config['maxLength'] > 0)
			{
				$config['length'] = $config['maxLength'];
			}

			unset($config['maxLength']);
		}

		return $config;
	}

	/**
	 * Generates the column definition SQL for a column
	 *
	 * @param array $config
	 * @return string
	 * @static
	 */
	public static function generateColumnDefinition($config)
	{
		$config = static::normalizeAttributeConfig($config);

		// Start the column definition
		switch ($config['column'])
		{
			case ColumnType::Char:
			{
				$def = 'CHAR('.$config['length'].')';
				break;
			}
			case ColumnType::Varchar:
			{
				$def = 'VARCHAR('.$config['length'].')';
				break;
			}
			case ColumnType::TinyInt:
			{
				$def = 'TINYINT('.$config['length'].')';
				break;
			}
			case ColumnType::SmallInt:
			{
				$def = 'SMALLINT('.$config['length'].')';
				break;
			}
			case ColumnType::MediumInt:
			{
				$def = 'MEDIUMINT('.$config['length'].')';
				break;
			}
			case ColumnType::Int:
			{
				$def = 'INT('.$config['length'].')';
				break;
			}
			case ColumnType::BigInt:
			{
				$def = 'BIGINT('.$config['length'].')';
				break;
			}
			case ColumnType::Decimal:
			{
				$def = 'DECIMAL('.$config['length'].','.$config['decimals'].')';
				break;
			}
			case ColumnType::Enum:
			{
				$def = 'ENUM(';
				$values = ArrayHelper::stringToArray($config['values']);

				foreach ($values as $i => $value)
				{
					if ($i > 0) $def .= ',';
					$def .= '\''.addslashes($value).'\'';
				}
				$def .= ')';
				break;
			}
			default:
			{
				$def = $config['column'];
			}
		}

		if (in_array($config['column'], static::$_numericColumnTypes))
		{
			if (!empty($config['unsigned']))
			{
				$def .= ' UNSIGNED';
			}

			if (!empty($config['zerofill']))
			{
				$def .= ' ZEROFILL';
			}
		}
		else if (in_array($config['column'], static::$_textualColumnTypes))
		{
			if (!empty($config['charset']))
			{
				$def .= ' CHARACTER SET '.$config['charset'];
			}

			if (!empty($config['collation']))
			{
				$def .= ' COLLATE '.$config['collation'];
			}
		}

		if (!empty($config['required']) || (isset($config['null']) && $config['null'] === false))
		{
			$def .= ' NOT NULL';
		}
		else
		{
			$def .= ' NULL';
		}

		if (isset($config['default']) && (is_string($config['default']) || is_bool($config['default']) || is_numeric($config['default'])))
		{
			if (is_string($config['default']) && !is_numeric($config['default']))
			{
				$def .= ' DEFAULT "'.$config['default'].'"';
			}
			else
			{
				$def .= ' DEFAULT '.(int)$config['default'];
			}
		}

		if (!empty($config['primaryKey']))
		{
			$def .= ' PRIMARY KEY';
		}

		return $def;
	}

	/**
	 * Prepares a table name for Yii to add its table prefix
	 *
	 * @static
	 * @param mixed $table The table name or an array of table names
	 * @return mixed The modified table name(s)
	 */
	public static function addTablePrefix($table)
	{
		if (is_array($table))
		{
			foreach ($table as $key => $t)
			{
				$table[$key] = static::addTablePrefix($t);
			}
		}
		else
		{
			$table = preg_replace('/^\w+/', craft()->db->tablePrefix.'\0', $table);
		}

		return $table;
	}

	/**
	 * Returns a foreign key name based on the table and column names.
	 *
	 * @param string $table
	 * @param string|array $columns
	 * @return string
	 */
	public static function getForeignKeyName($table, $columns)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = craft()->db->tablePrefix.$table.'_'.implode('_', $columns).'_fk';
		return static::normalizeDbObjectName($name);
	}

	/**
	 * Returns an index name based on the table, column names, and whether it should be unique.
	 *
	 * @param string $table
	 * @param string|array $columns
	 * @param bool $unique
	 * @return string
	 */
	public static function getIndexName($table, $columns, $unique = false)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = craft()->db->tablePrefix.$table.'_'.implode('_', $columns).($unique ? '_unq' : '').'_idx';
		return static::normalizeDbObjectName($name);
	}

	/**
	 * Returns a primary key name based on the table and column names.
	 *
	 * @param string $table
	 * @param string|array $columns
	 * @param bool $unique
	 * @return string
	 */
	public static function getPrimaryKeyName($table, $columns)
	{
		$columns = ArrayHelper::stringToArray($columns);
		$name = craft()->db->tablePrefix.$table.'_'.implode('_', $columns).'_pk';
		return static::normalizeDbObjectName($name);
	}

	/**
	 * Ensures that an object name is within the schema's limit.
	 *
	 * @static
	 * @param string $name
	 * @return string
	 */
	public static function normalizeDbObjectName($name)
	{
		// TODO: MySQL specific
		// MySQL indexes can't be more than 64 characters (see http://dev.mysql.com/doc/refman/5.0/en/identifiers.html)
		$maxLength = 64;

		$name = trim($name, '_');
		$nameLength = mb_strlen($name);

		if ($nameLength > $maxLength)
		{
			$parts = array_filter(explode('_', $name));
			$totalParts = count($parts);
			$totalLetters = $nameLength - ($totalParts-1);
			$maxLetters = $maxLength - ($totalParts-1);

			// Consecutive underscores could have put this name over the top
			if ($totalLetters > $maxLetters)
			{
				foreach ($parts as $i => $part)
				{
					$newLength = round($maxLetters * mb_strlen($part) / $totalLetters);
					$parts[$i] = mb_substr($part, 0, $newLength);
				}
			}

			$name = implode('_', $parts);

			// Just to be safe
			if (mb_strlen($name) > $maxLength)
			{
				$name = mb_substr($name, 0, $maxLength);
			}
		}

		return $name;
	}

	/**
	 * @static
	 * @return array
	 */
	public static function getAuditColumnConfig()
	{
		return array(
			'dateCreated' => array('column' => ColumnType::DateTime,  'required' => true),
			'dateUpdated' => array('column' => ColumnType::DateTime,  'required' => true),
			'uid'         => array('column' => ColumnType::Char, 'length' => 36, 'required' => true, 'default' => 0)
		);
	}

	/**
	 * @access private
	 * @static
	 */
	private static $_operators = array('not ', '!=', '<=', '>=', '<', '>', '=');

	/**
	 * Parses a service param value to a DbCommand where condition.
	 *
	 * @param string $key
	 * @param string|array $values
	 * @param array &$params
	 * @return mixed
	 */
	public static function parseParam($key, $values, &$params)
	{
		if ($values == 'not ')
		{
			return '';
		}

		$conditions = array();

		$values = ArrayHelper::stringToArray($values);

		if (!count($values))
		{
			return '';
		}

		if ($values[0] == 'and' || $values[0] == 'or')
		{
			$join = $values[0];
			array_shift($values);
		}
		else
		{
			$join = 'or';
		}

		foreach ($values as $value)
		{
			$operator = '=';

			foreach (static::$_operators as $testOperator)
			{
				// Does the value start with this operator?
				$length = mb_strlen($testOperator);

				if (strncmp(mb_strtolower($value), $testOperator, $length) == 0)
				{
					$value = mb_substr($value, $length);

					if ($testOperator == 'not ')
					{
						$operator = '!=';
					}
					else
					{
						$operator = $testOperator;
					}

					break;
				}
			}

			if ($value === null)
			{
				if ($operator == '=')
				{
					$conditions[] = $key.' is null';
				}
				else if ($operator == '!=')
				{
					$conditions[] = $key.' is not null';
				}
			}
			else
			{
				$param = ':p'.StringHelper::randomString(9);
				$params[$param] = trim($value);
				$conditions[] = $key.$operator.$param;
			}
		}

		if (count($conditions) == 1)
		{
			return $conditions[0];
		}
		else
		{
			array_unshift($conditions, $join);
			return $conditions;
		}
	}

	/**
	 * Parses a date param value to a DbCommand where condition.
	 *
	 * @param string $key
	 * @param string $operator
	 * @param string|array|DateTime $dates
	 * @param array &$params
	 * @return mixed
	 */
	public static function parseDateParam($key, $operator, $dates, &$params)
	{
		$conditions = array();

		$dates = ArrayHelper::stringToArray($dates);

		foreach ($dates as $date)
		{
			if (!$date instanceof \DateTime)
			{
				$date = DateTime::createFromString($date, craft()->getTimezone());
			}

			$param = ':p'.StringHelper::randomString(9);
			$params[$param] = DateTimeHelper::formatTimeForDb($date->getTimestamp());
			$conditions[] = $key.$operator.$param;
		}

		if (count($conditions) == 1)
		{
			return $conditions[0];
		}
		else
		{
			array_unshift($conditions, 'or');
			return $conditions;
		}
	}
}
