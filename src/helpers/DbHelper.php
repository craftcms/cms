<?php
namespace Craft;

/**
 * Class DbHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class DbHelper
{
	// Properties
	// =========================================================================

	/**
	 * The default column configs.
	 *
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
	 * Numeric column types.
	 *
	 * @var array
	 */
	private static $_numericColumnTypes = array(ColumnType::TinyInt, ColumnType::SmallInt, ColumnType::MediumInt, ColumnType::Int, ColumnType::BigInt, ColumnType::Decimal);

	/**
	 * Textual column types.
	 *
	 * @var array
	 */
	private static $_textualColumnTypes = array(ColumnType::Char, ColumnType::Varchar, ColumnType::TinyText, ColumnType::Text, ColumnType::MediumText, ColumnType::LongText);

	/**
	 * @var array
	 */
	private static $_operators = array('not ', '!=', '<=', '>=', '<', '>', '=');

	// Public Methods
	// =========================================================================

	/**
	 * Normalizes a column's config.
	 *
	 * Columns can be defined in 3 ways:
	 *
	 * 1. ColumnType::TypeName
	 * 2. array(ColumnType::TypeName [, 'other' => 'settings' ... ] )
	 * 3. array('column' => ColumnType::TypeName [, 'other' => 'settings' ... ] )
	 *
	 * This function normalizes on the 3rd, merges in the default config settings for the column type, and renames 'maxLength' to 'length'.
	 *
	 * @param string|array $config
	 *
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
			$config['charset'] = craft()->config->get('charset', ConfigFile::Db);
			$config['collation'] = craft()->config->get('collation', ConfigFile::Db);
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
	 * Generates the column definition SQL for a column.
	 *
	 * @param array $config
	 *
	 * @return string
	 */
	public static function generateColumnDefinition($config)
	{
		// Don't do anything to PKs.
		if ($config === ColumnType::PK)
		{
			return $config;
		}

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
	 * @param mixed $table The table name or an array of table names
	 *
	 * @deprecated Deprecated in Craft 2.2. Use {@link DbCommand::addTablePrefix() craft()->db->addTablePrefix()} instead.
	 * @return mixed The modified table name(s)
	 */
	public static function addTablePrefix($table)
	{
		// TODO: Add deprecation log in 3.0
		return craft()->db->addTablePrefix($table);
	}

	/**
	 * Returns a foreign key name based on the table and column names.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 *
	 * @deprecated Deprecated in Craft 2.2. Use {@link DbCommand::getForeignKeyName() craft()->db->getForeignKeyName()} instead.
	 * @return string
	 */
	public static function getForeignKeyName($table, $columns)
	{
		// TODO: Add deprecation log in 3.0
		return craft()->db->getForeignKeyName($table, $columns);
	}

	/**
	 * Returns an index name based on the table, column names, and whether it should be unique.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 * @param bool         $unique
	 *
	 * @deprecated Deprecated in Craft 2.2. Use {@link DbCommand::getIndexName() craft()->db->getIndexName()} instead.
	 * @return string
	 */
	public static function getIndexName($table, $columns, $unique = false)
	{
		// TODO: Add deprecation log in 3.0
		return craft()->db->getIndexName($table, $columns, $unique);

	}

	/**
	 * Returns a primary key name based on the table and column names.
	 *
	 * @param string       $table
	 * @param string|array $columns
	 *
	 * @deprecated Deprecated in Craft 2.2. Use {@link DbCommand::getPrimaryKeyName() craft()->db->getPrimaryKeyName()} instead.
	 * @return string
	 */
	public static function getPrimaryKeyName($table, $columns)
	{
		// TODO: Add deprecation log in 3.0
		return craft()->db->getPrimaryKeyName($table, $columns);
	}

	/**
	 * Ensures that an object name is within the schema's limit.
	 *
	 * @param string $name
	 *
	 * @deprecated Deprecated in Craft 2.2. Use {@link DbCommand::trimObjectName() craft()->db->trimObjectName()} instead.
	 * @return string
	 */
	public static function normalizeDbObjectName($name)
	{
		// TODO: Add deprecation log in 3.0
		return craft()->db->trimObjectName($name);
	}

	/**
	 * @return array
	 */
	public static function getAuditColumnConfig()
	{
		return array(
			'dateCreated' => array('column' => ColumnType::DateTime, 'required' => true),
			'dateUpdated' => array('column' => ColumnType::DateTime, 'required' => true),
			'uid'         => array('column' => ColumnType::Char, 'length' => 36, 'required' => true, 'default' => 0)
		);
	}

	/**
	 * Parses a service param value to a DbCommand where condition.
	 *
	 * @param string       $key
	 * @param string|array $values
	 * @param array        &$params
	 *
	 * @return mixed
	 */
	public static function parseParam($key, $values, &$params)
	{
		// Need to do a strict check here in case $values = true
		if ($values === 'not ')
		{
			return '';
		}

		$conditions = array();

		$values = ArrayHelper::stringToArray($values);

		if (!count($values))
		{
			return '';
		}

		$firstVal = StringHelper::toLowerCase(ArrayHelper::getFirstValue($values));

		if ($firstVal == 'and' || $firstVal == 'or')
		{
			$join = array_shift($values);
		}
		else
		{
			$join = 'or';
		}

		foreach ($values as $value)
		{
			static::_normalizeEmptyValue($value);
			$operator = static::_parseParamOperator($value);

			if (StringHelper::toLowerCase($value) == ':empty:')
			{
				if ($operator == '=')
				{
					$conditions[] = array('or', $key.' is null', $key.' = ""');
				}
				else
				{
					$conditions[] = array('and', $key.' is not null', $key.' != ""');
				}
			}
			else
			{
				// Find a unique param name
				$paramKey = ':'.str_replace('.', '', $key);
				$i = 1;
				while (isset($params[$paramKey.$i]))
				{
					$i++;
				}

				$param = $paramKey.$i;
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
	 * Normalizes date params and then sends them off to parseParam().
	 *
	 * @param string                $key
	 * @param string|array|DateTime $values
	 * @param array                 &$params
	 *
	 * @return mixed
	 */
	public static function parseDateParam($key, $values, &$params)
	{
		$normalizedValues = array();

		$values = ArrayHelper::stringToArray($values);

		if (!count($values))
		{
			return '';
		}

		if ($values[0] == 'and' || $values[0] == 'or')
		{
			$normalizedValues[] = $values[0];
			array_shift($values);
		}

		foreach ($values as $value)
		{
			// Is this an empty value?
			static::_normalizeEmptyValue($value);

			if ($value == ':empty:' || $value == 'not :empty:')
			{
				$normalizedValues[] = $value;

				// Sneak out early
				continue;
			}

			if (is_string($value))
			{
				$operator = static::_parseParamOperator($value);
			}
			else
			{
				$operator = '=';
			}

			if (!$value instanceof \DateTime)
			{
				$value = DateTime::createFromString($value, craft()->getTimeZone());
			}

			$normalizedValues[] = $operator.DateTimeHelper::formatTimeForDb($value->getTimestamp());
		}

		return static::parseParam($key, $normalizedValues, $params);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes “empty” values.
	 *
	 * @param stirng &$value The param value.
	 */
	private static function _normalizeEmptyValue(&$value)
	{
		if ($value === null)
		{
			$value = ':empty:';
		}
		else if (StringHelper::toLowerCase($value) == ':notempty:')
		{
			$value = 'not :empty:';
		}
	}

	/**
	 * Extracts the operator from a DB param and returns it.
	 *
	 * @param string &$value Te param value.
	 *
	 * @return string The operator.
	 */
	private static function _parseParamOperator(&$value)
	{
		foreach (static::$_operators as $testOperator)
		{
			// Does the value start with this operator?
			$operatorLength = strlen($testOperator);

			if (strncmp(StringHelper::toLowerCase($value), $testOperator, $operatorLength) == 0)
			{
				$value = mb_substr($value, $operatorLength);

				if ($testOperator == 'not ')
				{
					return '!=';
				}
				else
				{
					return $testOperator;
				}
			}
		}

		return '=';
	}
}
