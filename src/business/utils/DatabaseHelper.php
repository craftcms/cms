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
		PropertyType::TinyInt      => array('maxLength' => 4),
		PropertyType::SmallInt     => array('maxLength' => 6),
		PropertyType::MediumInt    => array('maxLength' => 9),
		PropertyType::Int          => array('maxLength' => 11),
		PropertyType::BigInt       => array('maxLength' => 20),
		PropertyType::TinyInt      => array('maxLength' => 4),
		PropertyType::Decimal      => array('maxLength' => 10),
		PropertyType::Boolean      => array('type '=> PropertyType::TinyInt, 'maxLength' => 1, 'unsigned' => true, 'required' => true, 'default' => false),
		PropertyType::Enum         => array('values' => array()),

		// Common model property types
		PropertyType::ClassName     => array('type' => PropertyType::Char, 'maxLength' => 150, 'required' => true),
		PropertyType::Email         => array('type' => PropertyType::Varchar, 'minLength' => 5),
		PropertyType::Handle        => array('type' => PropertyType::Char, 'maxLength' => 100, 'required' => true),
		PropertyType::Language      => array('type' => PropertyType::Char, 'maxLength' => 12, 'required' => true),
		PropertyType::Name          => array('type' => PropertyType::Varchar, 'maxLength' => 100),
		PropertyType::SortOrder     => array('type' => PropertyType::SmallInt, 'required' => true, 'unsigned' => true),
		PropertyType::Template      => array('type' => PropertyType::Varchar, 'maxLength' => 500),
		PropertyType::Version       => array('type' => PropertyType::Char, 'maxLength' => 15, 'required' => true),
		PropertyType::Url           => array('type' => PropertyType::Varchar, 'maxLength' => 255),
		PropertyType::Build         => array('type' => PropertyType::Int, 'required' => true, 'unsigned' => true),
		PropertyType::LicenseKey    => array('type' => PropertyType::Char, 'length' => 36, 'matchPattern' => '/[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/', 'required' => true),
		PropertyType::Json          => array('type' => PropertyType::Text),
		PropertyType::UnixTimeStamp => array('type' => PropertyType::Int)
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
				$def = 'DECIMAL('.$config['maxLength'].')';
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
			'date_created' => array('type' => PropertyType::UnixTimeStamp, 'required' => true, 'default' => 0),
			'date_updated' => array('type' => PropertyType::UnixTimeStamp, 'required' => true, 'default' => 0),
			'uid'          => array('type' => PropertyType::Char, 'maxLength' => 36, 'required' => true, 'default' => 0)
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
