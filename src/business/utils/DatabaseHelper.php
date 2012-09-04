<?php
namespace Blocks;

/**
 *
 */
class DatabaseHelper
{
	/**
	 * Default property settings
	 */
	protected static $propertyTypeDefaults = array(
		PropertyType::Char         => array('maxLength' => 255),
		PropertyType::Varchar      => array('maxLength' => 255),
		PropertyType::Number       => array('maxLength' => 10, 'min' => -2147483648, 'max' => 2147483647, 'decimals' => 0),
		PropertyType::TinyInt      => array('maxLength' => 4),
		PropertyType::SmallInt     => array('maxLength' => 6),
		PropertyType::MediumInt    => array('maxLength' => 9),
		PropertyType::Int          => array('maxLength' => 11),
		PropertyType::BigInt       => array('maxLength' => 20),
		PropertyType::TinyInt      => array('maxLength' => 4),
		PropertyType::Decimal      => array('maxLength' => 10, 'decimals' => 2),
		PropertyType::Boolean      => array('type '=> PropertyType::TinyInt, 'maxLength' => 1, 'unsigned' => true, 'required' => true, 'default' => false),
		PropertyType::Enum         => array('values' => array()),

		// Common model property types
		PropertyType::ClassName    => array('type' => PropertyType::Char, 'maxLength' => 150, 'required' => true),
		PropertyType::Email        => array('type' => PropertyType::Varchar, 'minLength' => 5),
		PropertyType::Handle       => array('type' => PropertyType::Char, 'maxLength' => 100, 'required' => true),
		PropertyType::Language     => array('type' => PropertyType::Char, 'maxLength' => 12, 'required' => true),
		PropertyType::Name         => array('type' => PropertyType::Varchar, 'maxLength' => 100, 'required' => true),
		PropertyType::SortOrder    => array('type' => PropertyType::SmallInt, 'required' => true, 'unsigned' => true),
		PropertyType::Template     => array('type' => PropertyType::Varchar, 'maxLength' => 500),
		PropertyType::Version      => array('type' => PropertyType::Char, 'maxLength' => 15, 'required' => true),
		PropertyType::Url          => array('type' => PropertyType::Varchar, 'maxLength' => 255),
		PropertyType::Build        => array('type' => PropertyType::Int, 'required' => true, 'unsigned' => true),
		PropertyType::LicenseKey   => array('type' => PropertyType::Char, 'length' => 36, 'matchPattern' => '/[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/', 'required' => true),
		PropertyType::Json         => array('type' => PropertyType::Text),
	);

	/**
	 * Normalize property config
	 *
	 * @param $config
	 * @return array
	 */
	public static function normalizePropertyConfig($config)
	{
		if (is_string($config))
		{
			$config = array('type' => $config);
		}
		else if (!isset($config['type']))
		{
			$config['type'] = ModelHelper::getPropertyType($config);
		}

		// Merge in the default settings
		if (isset(static::$propertyTypeDefaults[$config['type']]))
		{
			$config = array_merge(static::$propertyTypeDefaults[$config['type']], $config);

			// Override the type if the default settings specifies it
			if (isset(static::$propertyTypeDefaults[$config['type']]['type']))
			{
				$newType = static::$propertyTypeDefaults[$config['type']]['type'];
				$config['type'] = $newType;

				// ...And merge in the new type's settings...
				$config = static::normalizePropertyConfig($config);
			}
			// Handle number columns
			else if ($config['type'] == PropertyType::Number)
			{
				$config = static::getNumberColumnConfig($config['min'], $config['max'], $config['decimals']);
			}
		}

		return $config;
	}

	private static $_intColumnTypes = array(
		PropertyType::TinyInt   => 128,
		PropertyType::SmallInt  => 32768,
		PropertyType::MediumInt => 8388608,
		PropertyType::Int       => 2147483648,
		PropertyType::BigInt    => 9223372036854775808
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
		$min = is_numeric($min) ? $min : static::$propertyTypeDefaults[PropertyType::Number]['min'];
		$max = is_numeric($max) ? $max : static::$propertyTypeDefaults[PropertyType::Number]['max'];
		$decimals = is_numeric($decimals) && $decimals > 0 ? intval($decimals) : 0;

		// Unsigned?
		$config['unsigned'] = ($min >= 0);

		// Figure out the max length
		$maxAbsSize = intval($config['unsigned'] ? $max : max(abs($min), abs($max)));
		$config['maxLength'] = ($maxAbsSize ? strlen($maxAbsSize) : 0) + $decimals;

		// Int or decimal?
		if ($decimals == 0)
		{
			// Figure out the smallest possible int column type that will fit our min/max
			foreach (static::$_intColumnTypes as $colType => $size)
			{
				if ($config['unsigned'])
				{
					if ($max < $size * 2)
						break;
				}
				else
				{
					if ($min >= -$size && $max < $size)
						break;
				}
			}

			$config['type'] = $colType;
		}
		else
		{
			$config['type'] = PropertyType::Decimal;
			$config['decimals'] = $decimals;
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
		$config = static::normalizePropertyConfig($config);

		// Treat strict lengths as max lengths when defining columns
		if (isset($config['length']) && is_numeric($config['length']) && $config['length'] > 0)
			$config['maxLength'] = $config['length'];

		// Start the column definition
		switch ($config['type'])
		{
			case PropertyType::Char:
				$def = 'CHAR('.$config['maxLength'].')';
				break;
			case PropertyType::Varchar:
				$def = 'VARCHAR('.$config['maxLength'].')';
				break;
			case PropertyType::TinyInt:
				$def = 'TINYINT('.$config['maxLength'].')';
				break;
			case PropertyType::SmallInt:
				$def = 'SMALLINT('.$config['maxLength'].')';
				break;
			case PropertyType::MediumInt:
				$def = 'MEDIUMINT('.$config['maxLength'].')';
				break;
			case PropertyType::Int:
				$def = 'INT('.$config['maxLength'].')';
				break;
			case PropertyType::BigInt:
				$def = 'BIGINT('.$config['maxLength'].')';
				break;
			case PropertyType::Decimal:
				$def = 'DECIMAL('.$config['maxLength'].','.$config['decimals'].')';
				break;
			case PropertyType::Enum:
				$def = 'ENUM(';
				$values = is_array($config['values']) ? $config['values'] : explode(',', $config['values']);
				foreach ($values as $i => $value)
				{
					if ($i > 0) $def .= ',';
					$def .= '\''.addslashes($value).'\'';
				}
				$def .= ')';
				break;
			default:
				$def = $config['type'];
		}

		if (isset($config['unsigned']) && $config['unsigned'] === true)
			$def .= ' UNSIGNED';

		if (isset($config['zerofill']) && $config['zerofill'] === true)
			$def .= ' ZEROFILL';

		if (isset($config['required']) && $config['required'] === true)
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
	public static function getAuditColumnDefinition()
	{
		return array(
			'dateCreated' => array('type' => PropertyType::Int, 'required' => true, 'default' => 0),
			'dateUpdated' => array('type' => PropertyType::Int, 'required' => true, 'default' => 0),
			'uid'         => array('type' => PropertyType::Char, 'maxLength' => 36, 'required' => true, 'default' => 0)
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
