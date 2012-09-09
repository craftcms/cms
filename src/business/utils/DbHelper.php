<?php
namespace Blocks;

/**
 *
 */
class DbHelper
{
	/**
	 * Default column configs
	 */
	public static $columnTypeDefaults = array(
		ColumnType::Char         => array('maxLength' => 255),
		ColumnType::Varchar      => array('maxLength' => 255),
		ColumnType::TinyInt      => array('maxLength' => 4),
		ColumnType::SmallInt     => array('maxLength' => 6),
		ColumnType::MediumInt    => array('maxLength' => 9),
		ColumnType::Int          => array('maxLength' => 11),
		ColumnType::BigInt       => array('maxLength' => 20),
		ColumnType::TinyInt      => array('maxLength' => 4),
		ColumnType::Decimal      => array('maxLength' => 10, 'decimals' => 2),
		ColumnType::Enum         => array('values' => array()),
	);

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
		if (isset(static::$columnTypeDefaults[$config['column']]))
			$config = array_merge(static::$columnTypeDefaults[$config['column']], $config);

		// Rename 'maxLength' to 'length'
		if (isset($config['maxLength']))
		{
			if (!isset($config['length']) && is_numeric($config['maxLength']) && $config['maxLength'] > 0)
				$config['length'] = $config['maxLength'];

			unset($config['maxLength']);
		}

		return $config;
	}

	/**
	 * Integer column sizes
	 *
	 * @static
	 * @var array
	 */
	public static $intColumnSizes = array(
		ColumnType::TinyInt   => 128,
		ColumnType::SmallInt  => 32768,
		ColumnType::MediumInt => 8388608,
		ColumnType::Int       => 2147483648,
		ColumnType::BigInt    => 9223372036854775808
	);

	/**
	 * Returns a number column config, taking the min, max, and number of decimal points into account.
	 *
	 * @static
	 * @param number $min
	 * @param number $max
	 * @param int $decimals
	 * @return array
	 */
	public static function getNumberColumnConfig($min = null, $max = null, $decimals = null)
	{
		$config = array();

		// Normalize the arguments
		$config['min'] = is_numeric($min) ? $min : -static::$intColumnSizes[ColumnType::Int];
		$config['max'] = is_numeric($max) ? $max : static::$intColumnSizes[ColumnType::Int]-1;
		$config['decimals'] = is_numeric($decimals) && $decimals > 0 ? intval($decimals) : 0;

		// Unsigned?
		$config['unsigned'] = ($config['min'] >= 0);

		// Figure out the max length
		$maxAbsSize = intval($config['unsigned'] ? $config['max'] : max(abs($config['min']), abs($config['max'])));
		$config['maxLength'] = ($maxAbsSize ? strlen($maxAbsSize) : 0) + $config['decimals'];

		// Decimal or int?
		if ($config['decimals'] > 0)
		{
			$config['column'] = ColumnType::Decimal;
		}
		else
		{
			// Figure out the smallest possible int column type that will fit our min/max
			foreach (static::$intColumnSizes as $colType => $size)
			{
				if ($config['unsigned'])
				{
					if ($config['max'] < $size * 2)
						break;
				}
				else
				{
					if ($config['min'] >= -$size && $config['max'] < $size)
						break;
				}
			}

			$config['column'] = $colType;
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

		if (!empty($config['unsigned']))
			$def .= ' UNSIGNED';

		if (!empty($config['zerofill']))
			$def .= ' ZEROFILL';

		if (!empty($config['required']))
			$def .= ' NOT NULL';
		else
			$def .= ' NULL';

		if (isset($config['default']))
		{
			if (is_string($config['default']) && !is_numeric($config['default']))
				$def .= ' DEFAULT "'.$config['default'].'"';
			else
				$def .= ' DEFAULT '.(int)$config['default'];
		}

		return $def;
	}

	/**
	 * @static
	 * @return array
	 */
	public static function getAuditColumnConfig()
	{
		return array(
			'dateCreated' => array('column' => ColumnType::Int, 'unsigned' => true, 'required' => true, 'default' => 0),
			'dateUpdated' => array('column' => ColumnType::Int, 'unsigned' => true, 'required' => true, 'default' => 0),
			'uid'         => array('column' => ColumnType::Char, 'length' => 36, 'required' => true, 'default' => 0)
		);
	}

	/**
	 * @access protected
	 * @static
	 */
	protected static $operators = array(
		'not ' => '!=',
		'!'    => '!=',
		'<='   => '<=',
		'>='   => '>=',
		'<'    => '<',
		'>'    => '>',
		'='    => '=',
	);

	/**
	 * Parses a service param value to a DbCommand where condition.
	 *
	 * @param string $key
	 * @param string $value
	 * @param array &$params
	 * @return mixed
	 */
	public static function parseParam($key, $value, &$params)
	{
		foreach (static::$operators as $operator => $sqlOperator)
		{
			$length = strlen($operator);
			if (strncmp(strtolower($value), $operator, $length) == 0)
			{
				$value = substr($value, $length);
				break;
			}
		}

		if ($value === null)
		{
			if ($operator == '=')
				return $key.' is null';
			else if ($operator == '!=')
				return $key.' is not null';
		}
		else
		{
			$params[':'.$key] = trim($value);
			return $key.$sqlOperator.':'.$key;
		}
	}
}
