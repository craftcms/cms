<?php
namespace Blocks;

/**
 *
 */
class DbHelper
{
	/**
	 * Default property settings
	 */
	protected static $propertyTypeDefaults = array(
		AttributeType::Char         => array('maxLength' => 255),
		AttributeType::Varchar      => array('maxLength' => 255),
		AttributeType::Number       => array('maxLength' => 10, 'min' => -2147483648, 'max' => 2147483647, 'decimals' => 0),
		AttributeType::TinyInt      => array('maxLength' => 4),
		AttributeType::SmallInt     => array('maxLength' => 6),
		AttributeType::MediumInt    => array('maxLength' => 9),
		AttributeType::Int          => array('maxLength' => 11),
		AttributeType::BigInt       => array('maxLength' => 20),
		AttributeType::TinyInt      => array('maxLength' => 4),
		AttributeType::Decimal      => array('maxLength' => 10, 'decimals' => 2),
		AttributeType::Boolean      => array('type '=> AttributeType::TinyInt, 'maxLength' => 1, 'unsigned' => true, 'required' => true, 'default' => false),
		AttributeType::Enum         => array('values' => array()),

		// Common model property types
		AttributeType::ClassName     => array('type' => AttributeType::Char, 'maxLength' => 150, 'required' => true),
		AttributeType::Email         => array('type' => AttributeType::Varchar, 'minLength' => 5),
		AttributeType::Handle        => array('type' => AttributeType::Char, 'maxLength' => 100, 'required' => true),
		AttributeType::Language      => array('type' => AttributeType::Char, 'maxLength' => 12, 'required' => true),
		AttributeType::Name          => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		AttributeType::SortOrder     => array('type' => AttributeType::SmallInt, 'required' => true, 'unsigned' => true),
		AttributeType::Template      => array('type' => AttributeType::Varchar, 'maxLength' => 500),
		AttributeType::Version       => array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => true),
		AttributeType::Url           => array('type' => AttributeType::Varchar, 'maxLength' => 255),
		AttributeType::Build         => array('type' => AttributeType::Int, 'required' => true, 'unsigned' => true),
		AttributeType::LicenseKey    => array('type' => AttributeType::Char, 'length' => 36, 'matchPattern' => '/[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/', 'required' => true),
		AttributeType::Json          => array('type' => AttributeType::Text),
		AttributeType::UnixTimeStamp => array('type' => AttributeType::Int)
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
			$config['type'] = ModelHelper::getAttributeType($config);
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
			else if ($config['type'] == AttributeType::Number)
			{
				$config = static::getNumberColumnConfig($config['min'], $config['max'], $config['decimals']);
			}
		}

		return $config;
	}

	private static $_intColumnTypes = array(
		AttributeType::TinyInt   => 128,
		AttributeType::SmallInt  => 32768,
		AttributeType::MediumInt => 8388608,
		AttributeType::Int       => 2147483648,
		AttributeType::BigInt    => 9223372036854775808
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
		$min = is_numeric($min) ? $min : static::$propertyTypeDefaults[AttributeType::Number]['min'];
		$max = is_numeric($max) ? $max : static::$propertyTypeDefaults[AttributeType::Number]['max'];
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
			$config['type'] = AttributeType::Decimal;
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
			case AttributeType::Char:
				$def = 'CHAR('.$config['maxLength'].')';
				break;
			case AttributeType::Varchar:
				$def = 'VARCHAR('.$config['maxLength'].')';
				break;
			case AttributeType::TinyInt:
				$def = 'TINYINT('.$config['maxLength'].')';
				break;
			case AttributeType::SmallInt:
				$def = 'SMALLINT('.$config['maxLength'].')';
				break;
			case AttributeType::MediumInt:
				$def = 'MEDIUMINT('.$config['maxLength'].')';
				break;
			case AttributeType::Int:
				$def = 'INT('.$config['maxLength'].')';
				break;
			case AttributeType::BigInt:
				$def = 'BIGINT('.$config['maxLength'].')';
				break;
			case AttributeType::Decimal:
				$def = 'DECIMAL('.$config['maxLength'].','.$config['decimals'].')';
				break;
			case AttributeType::Enum:
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
	public static function getAuditColumnConfig()
	{
		return array(
			'dateCreated' => array('type' => AttributeType::UnixTimeStamp, 'required' => true, 'default' => 0),
			'dateUpdated' => array('type' => AttributeType::UnixTimeStamp, 'required' => true, 'default' => 0),
			'uid'         => array('type' => AttributeType::Char, 'maxLength' => 36, 'required' => true, 'default' => 0)
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
