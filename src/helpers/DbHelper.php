<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use craft\app\Craft;
use craft\app\dates\DateTime;
use craft\app\enums\ColumnType;
use craft\app\enums\ConfigFile;

/**
 * Class DbHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	public static $columnTypeDefaults = [
		ColumnType::Char         => ['maxLength' => 255],
		ColumnType::Varchar      => ['maxLength' => 255],
		ColumnType::TinyInt      => ['maxLength' => 4],
		ColumnType::SmallInt     => ['maxLength' => 6],
		ColumnType::MediumInt    => ['maxLength' => 9],
		ColumnType::Int          => ['maxLength' => 11],
		ColumnType::BigInt       => ['maxLength' => 20],
		ColumnType::Decimal      => ['maxLength' => 10, 'decimals' => 2],
		ColumnType::Enum         => ['values' => []],
	];

	/**
	 * Numeric column types.
	 *
	 * @var array
	 */
	private static $_numericColumnTypes = [ColumnType::TinyInt, ColumnType::SmallInt, ColumnType::MediumInt, ColumnType::Int, ColumnType::BigInt, ColumnType::Decimal];

	/**
	 * Textual column types.
	 *
	 * @var array
	 */
	private static $_textualColumnTypes = [ColumnType::Char, ColumnType::Varchar, ColumnType::TinyText, ColumnType::Text, ColumnType::MediumText, ColumnType::LongText];

	/**
	 * @var array
	 */
	private static $_operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

	// Public Methods
	// =========================================================================

	/**
	 * Normalizes a column's config.
	 *
	 * Columns can be defined in 3 ways:
	 *
	 * 1. ColumnType::TypeName
	 * 2. [ColumnType::TypeName [, 'other' => 'settings' ... ] ]
	 * 3. ['column' => ColumnType::TypeName [, 'other' => 'settings' ... ] ]
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
			$config = ['column' => $config];
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
			$config['charset'] = Craft::$app->config->get('charset', ConfigFile::Db);
			$config['collation'] = Craft::$app->config->get('collation', ConfigFile::Db);
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
				$values = ArrayHelper::toArray($config['values']);

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
	 * @return array
	 */
	public static function getAuditColumnConfig()
	{
		return [
			'dateCreated' => ['column' => ColumnType::DateTime, 'required' => true],
			'dateUpdated' => ['column' => ColumnType::DateTime, 'required' => true],
			'uid'         => ['column' => ColumnType::Char, 'length' => 36, 'required' => true, 'default' => 0]
		];
	}

	/**
	 * Returns the maximum number of bytes a given textual column type can hold for a given database.
	 *
	 * @param        $columnType The textual column type to check.
	 * @param string $database   The type of database to use.
	 *
	 * @return int The storage capacity of the column type in bytes.
	 */
	public static function getTextualColumnStorageCapacity($columnType, $database = 'mysql')
	{
		switch ($database)
		{
			case 'mysql':
			{
				switch ($columnType)
				{
					case ColumnType::TinyText:
					{
						// 255 bytes
						return 255;
					}

					case ColumnType::Text:
					{
						// 65k
						return 65535;
					}

					case ColumnType::MediumText:
					{
						// 16MB
						return 16777215;
					}

					case ColumnType::LongText:
					{
						// 4GB
						return 4294967295;
					}
				}

				break;
			}
		}
	}

	/**
	 * Given a length of a piece of content, returns the underlying database column type to use for saving.
	 *
	 * @param $contentLength
	 *
	 * @return string
	 */
	public static function getTextualColumnTypeByContentLength($contentLength)
	{
		if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::TinyText))
		{
			return ColumnType::Varchar;
		}
		else if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::Text))
		{
			return ColumnType::Text;
		}
		else if ($contentLength <= static::getTextualColumnStorageCapacity(ColumnType::MediumText))
		{
			return ColumnType::MediumText;
		}
		else
		{
			return ColumnType::LongText;
		}
	}

	/**
	 * Escapes commas and asterisks in a string so they are not treated as special characters in
	 * [[DbHelper::parseParam()]].
	 *
	 * @param string $value The param value.
	 *
	 * @return string The escaped param value.
	 */
	public static function escapeParam($value)
	{
		return str_replace([',', '*'], ['\,', '\*'], $value);
	}

	/**
	 * Parses a query param value and returns a [[\CDbCommand::where()]]-compatible condition.
	 *
	 * If the `$value` is a string, it will automatically be converted to an array, split on any commas within the
	 * string (via [[ArrayHelper::toArray()]]). If that is not desired behavior, you can escape the comma
	 * with a backslash before it.
	 *
	 * The first value can be set to either `'and'` or `'or'` to define whether *all* of the values must match, or
	 * *any*. If it’s neither `'and'` nor `'or'`, then `'or'` will be assumed.
	 *
	 * Values can begin with the operators `'not '`, `'!='`, `'<='`, `'>='`, `'<'`, `'>'`, or `'='`. If they don’t,
	 * `'='` will be assumed.
	 *
	 * Values can also be set to either `':empty:'` or `':notempty:'` if you want to search for empty or non-empty
	 * database values. (An “empty” value is either NULL or an empty string of text).
	 *
	 * @param string       $column  The database column that the param is targeting.
	 * @param string|array $value   The param value(s).
	 * @param array        &$params The [[\CDbCommand::$params]] array.
	 *
	 * @return mixed
	 */
	public static function parseParam($column, $value, &$params)
	{
		// Need to do a strict check here in case $value = true
		if ($value === 'not ')
		{
			return '';
		}

		$conditions = [];

		$value = ArrayHelper::toArray($value);

		if (!count($value))
		{
			return '';
		}

		$firstVal = StringHelper::toLowerCase(ArrayHelper::getFirstValue($value));

		if ($firstVal == 'and' || $firstVal == 'or')
		{
			$join = array_shift($value);
		}
		else
		{
			$join = 'or';
		}

		foreach ($value as $val)
		{
			static::_normalizeEmptyValue($val);
			$operator = static::_parseParamOperator($val);

			if (StringHelper::toLowerCase($val) == ':empty:')
			{
				if ($operator == '=')
				{
					$conditions[] = ['or', $column.' is null', $column.' = ""'];
				}
				else
				{
					$conditions[] = ['and', $column.' is not null', $column.' != ""'];
				}
			}
			else
			{
				// Trim any whitespace from the value
				$val = trim($val);

				// This could be a LIKE condition
				if ($operator == '=' || $operator == '!=')
				{
					$val = preg_replace('/^\*|(?<!\\\)\*$/', '%', $val, -1, $count);
					$like = (bool) $count;
				}
				else
				{
					$like = false;
				}

				// Unescape any asterisks
				$val = str_replace('\*', '*', $val);

				if ($like)
				{
					$conditions[] = [($operator == '=' ? 'like' : 'not like'), $column, $val];
				}
				else
				{
					// Find a unique param name
					$paramKey = ':'.str_replace('.', '', $column);
					$i = 1;

					while (isset($params[$paramKey.$i]))
					{
						$i++;
					}

					$param = $paramKey.$i;
					$params[$param] = $val;

					$conditions[] = $column.$operator.$param;
				}
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
	 * @param string                $column
	 * @param string|array|DateTime $value
	 * @param array                 &$params
	 *
	 * @return mixed
	 */
	public static function parseDateParam($column, $value, &$params)
	{
		$normalizedValues = [];

		$value = ArrayHelper::toArray($value);

		if (!count($value))
		{
			return '';
		}

		if ($value[0] == 'and' || $value[0] == 'or')
		{
			$normalizedValues[] = $value[0];
			array_shift($value);
		}

		foreach ($value as $val)
		{
			// Is this an empty value?
			static::_normalizeEmptyValue($val);

			if ($val == ':empty:' || $val == 'not :empty:')
			{
				$normalizedValues[] = $val;

				// Sneak out early
				continue;
			}

			if (is_string($val))
			{
				$operator = static::_parseParamOperator($val);
			}
			else
			{
				$operator = '=';
			}

			if (!$val instanceof \DateTime)
			{
				$val = DateTime::createFromString($val, Craft::$app->getTimeZone());
			}

			$normalizedValues[] = $operator.DateTimeHelper::formatTimeForDb($val->getTimestamp());
		}

		return static::parseParam($column, $normalizedValues, $params);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes “empty” values.
	 *
	 * @param string &$value The param value.
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
