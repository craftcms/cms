<?php
namespace Blocks;

/**
 *
 */
class ModelHelper
{
	/**
	 * Default attribute configs
	 *
	 * @static
	 * @var array
	 */
	public static $attributeTypeDefaults = array(
		AttributeType::Mixed      => array('column' => ColumnType::Text),
		AttributeType::Bool       => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
		AttributeType::Build      => array('column' => ColumnType::Int, 'unsigned' => true),
		AttributeType::ClassName  => array('maxLength' => 150, 'column' => ColumnType::Char),
		AttributeType::DateTime   => array('column' => ColumnType::Int),
		AttributeType::Email      => array('minLength' => 5, 'column' => ColumnType::Varchar),
		AttributeType::Enum       => array('column' => ColumnType::Enum),
		AttributeType::Handle     => array('maxLength' => 100, 'reservedWords' => 'id,dateCreated,dateUpdated,uid,title', 'column' => ColumnType::Char),
		AttributeType::Language   => array('maxLength' => 12, 'column' => ColumnType::Char),
		AttributeType::LicenseKey => array('length' => 36, 'matchPattern' => '/[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/', 'required' => true, 'column' => ColumnType::Char),
		AttributeType::Name       => array('maxLength' => 100, 'column' => ColumnType::Varchar),
		AttributeType::Number     => array('min' => null, 'max' => null, 'decimals' => 0),
		AttributeType::Slug       => array('maxLength' => 50, 'column' => ColumnType::Char),
		AttributeType::SortOrder  => array('column' => ColumnType::SmallInt, 'unsigned' => true),
		AttributeType::Template   => array('maxLength' => 500, 'column' => ColumnType::Varchar),
		AttributeType::Url        => array('maxLength' => 255, 'column' => ColumnType::Varchar),
		AttributeType::Version    => array('maxLength' => 15, 'column' => ColumnType::Char),
	);

	/**
	 * Integer column sizes
	 *
	 * @static
	 * @access private
	 * @var array
	 */
	private static $_intColumnSizes = array(
		ColumnType::TinyInt   => 128,
		ColumnType::SmallInt  => 32768,
		ColumnType::MediumInt => 8388608,
		ColumnType::Int       => 2147483648,
		ColumnType::BigInt    => 9223372036854775808
	);

	/**
	 * Normalizes an attribute's config.
	 *
	 * Attributes can be defined in 3 ways:
	 *
	 * 1. AttributeType::TypeName
	 * 2. array(AttributeType::TypeName [, 'other' => 'settings' ... ] )
	 * 3. array('type' => AttributeType::TypeName [, 'other' => 'settings' ... ] )
	 *
	 * This function normalizes on the 3rd, and merges in the default config settings for the attribute type,
	 * merges in the default column settings if 'column' is set, and sets the 'unsigned', 'min', and 'max' values for integers.
	 *
	 * @param string|array $config
	 * @return array
	 */
	public static function normalizeAttributeConfig($config)
	{
		if (is_string($config))
		{
			$config = array('type' => $config);
		}
		else if (!isset($config['type']))
		{
			$config['type'] = isset($config[0]) ? $config[0] : AttributeType::String;
		}

		// Merge in the default attribute + column configs
		if (isset(static::$attributeTypeDefaults[$config['type']]))
			$config = array_merge(static::$attributeTypeDefaults[$config['type']], $config);

		// Set the column type, min, and max values for Number attributes
		if ($config['type'] == AttributeType::Number && !isset($config['column']))
		{
			$numberConfig = static::getNumberColumnConfig($config['min'], $config['max'], $config['decimals']);
			$config = array_merge($config, $numberConfig);
		}

		// Add in DB column-specific settings
		if (isset($config['column']))
		{
			if (isset(DbHelper::$columnTypeDefaults[$config['column']]))
				$config = array_merge(DbHelper::$columnTypeDefaults[$config['column']], $config);

			// Add unsigned, min, and max settings to number columns
			if (isset(static::$_intColumnSizes[$config['column']]))
			{
				if (!isset($config['unsigned']))
					$config['unsigned'] = (isset($config['min']) && $config['min'] >= 0);

				$maxSize = static::$_intColumnSizes[$config['column']];
				$minMin = $config['unsigned'] ? 0 : -$maxSize;
				$maxMax = ($config['unsigned'] ? $maxSize * 2 : $maxSize) - 1;

				if (!isset($config['min']) || $config['min'] < $minMin)
					$config['min'] = $minMin;

				if (!isset($config['max']) || $config['max'] > $maxMax)
					$config['max'] = $maxMax;
			}
		}

		return $config;
	}

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
		$config['min'] = is_numeric($min) ? $min : -static::$_intColumnSizes[ColumnType::Int]['min'];
		$config['max'] = is_numeric($max) ? $max : static::$_intColumnSizes[ColumnType::Int]['max']-1;
		$config['decimals'] = is_numeric($decimals) && $decimals > 0 ? intval($decimals) : 0;

		// Unsigned?
		$config['unsigned'] = ($config['min'] >= 0);

		// Figure out the max length
		$maxAbsSize = intval($config['unsigned'] ? $config['max'] : max(abs($config['min']), abs($config['max'])));
		$config['length'] = ($maxAbsSize ? strlen($maxAbsSize) : 0) + $config['decimals'];

		// Decimal or int?
		if ($config['decimals'] > 0)
		{
			$config['column'] = ColumnType::Decimal;
		}
		else
		{
			// Figure out the smallest possible int column type that will fit our min/max
			foreach (static::$_intColumnSizes as $colType => $size)
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
	 * Populates any default values that are defined for a model.
	 *
	 * @static
	 * @param BaseModel|BaseRecord $model
	 */
	public static function populateAttributeDefaults($model)
	{
		foreach ($model->defineAttributes() as $name => $config)
		{
			$config = static::normalizeAttributeConfig($config);
			if (isset($config['default']))
				$model->setAttribute($name, $config['default']);
		}
	}

	/**
	 * Returns the rules array used by CModel.
	 *
	 * @static
	 * @param BaseModel|BaseRecord $model
	 * @return array
	 */
	public static function getRules($model)
	{
		$rules = array();

		$uniques = array();
		$required = array();
		$emails = array();
		$urls = array();
		$strictLengths = array();
		$minLengths = array();
		$maxLengths = array();

		$integerTypes = array(AttributeType::Number, ColumnType::TinyInt, ColumnType::SmallInt, ColumnType::MediumInt, ColumnType::Int, ColumnType::BigInt);
		$numberTypes = $integerTypes;
		$numberTypes[] = ColumnType::Decimal;

		$attributes = $model->defineAttributes();

		foreach ($attributes as $name => $config)
		{
			$config = static::normalizeAttributeConfig($config);

			switch ($config['type'])
			{
				case AttributeType::DateTime:
				{
					$rules[] = array($name, 'Blocks\DateTimeValidator');
					break;
				}

				case AttributeType::Email:
				{
					$emails[] = $name;
					break;
				}

				case Attributetype::Enum:
				{
					$rules[] = array($name, 'in', 'range' => ArrayHelper::stringToArray($config['values']));
					break;
				}

				case AttributeType::Handle:
				{
					$rules[] = array($name, 'Blocks\HandleValidator', 'reservedWords' => ArrayHelper::stringToArray($config['reservedWords']));
					break;
				}

				case AttributeType::Language:
				{
					$rules[] = array($name, 'Blocks\LanguageValidator');
					break;
				}

				case AttributeType::Number:
				{
					$rule = array($name, 'Blocks\LocaleNumberValidator');

					if (isset($config['min']) && is_numeric($config['min']))
						$rule['min'] = $config['min'];

					if (isset($config['max']) && is_numeric($config['max']))
						$rule['max'] = $config['max'];

					if (($config['type'] == AttributeType::Number && empty($config['decimals'])) || in_array($config['type'], $integerTypes))
						$rule['integerOnly'] = true;

					$rules[] = $rule;
				}

				case AttributeType::Url:
				{
					$urls[] = $name;
					break;
				}
			}

			// Remember if it's a license key
			$isLicenseKey = ($config['type'] == AttributeType::LicenseKey);

			$config = ModelHelper::normalizeAttributeConfig($config);

			// Uniques
			if (!empty($config['unique']))
				$uniques[] = $name;

			// Required
			if (!empty($config['required']))
				$required[] = $name;

			// License keys' length=36 is redundant in the context of validation, since matchPattern already enforces 36 chars
			if ($isLicenseKey)
				unset($config['length']);

			// Lengths
			if (isset($config['length']) && is_numeric($config['length']))
			{
				$strictLengths[(string)$config['length']][] = $name;
			}
			else
			{
				// Only worry about min- and max-lengths if a strict length isn't set
				if (isset($config['minLength']) && is_numeric($config['minLength']))
					$minLengths[(string)$config['minLength']][] = $name;

				if (isset($config['maxLength']) && is_numeric($config['maxLength']))
					$maxLengths[(string)$config['maxLength']][] = $name;
			}

			// Regex pattern matching
			if (!empty($config['matchPattern']))
				$rules[] = array($name, 'match', 'pattern' => $config['matchPattern']);
		}

		// Catch any unique/required indexes, if this is a BaseRecord instance
		if ($model instanceof BaseRecord)
		{
			foreach ($model->defineIndexes() as $config)
			{
				$unique = !empty($config['unique']);
				$required = !empty($config['required']);

				if ($unique || $required)
				{
					$columns = ArrayHelper::stringToArray($config['columns']);

					if ($unique)
					{
						if (count($columns) == 1)
						{
							$uniques[] = $columns[0];
						}
						else
						{
							$initialColumn = array_shift($columns);
							$rules[] = array($initialColumn, 'Blocks\CompositeUniqueValidator', 'with' => implode(',', $columns));
						}
					}

					if ($required)
					{
						$required = array_merge($required, $columns);
					}
				}
			}
		}

		if ($uniques)
			$rules[] = array(implode(',', $uniques), 'unique');

		if ($required)
			$rules[] = array(implode(',', $required), 'required');

		if ($emails)
			$rules[] = array(implode(',', $emails), 'email');

		if ($urls)
			$rules[] = array(implode(',', $urls), 'url', 'defaultScheme' => 'http');

		if ($strictLengths)
		{
			foreach ($strictLengths as $strictLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'is' => (int)$strictLength);
			}
		}

		if ($minLengths)
		{
			foreach ($minLengths as $minLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'min' => (int)$minLength);
			}
		}

		if ($maxLengths)
		{
			foreach ($maxLengths as $maxLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'max' => (int)$maxLength);
			}
		}

		$rules[] = array(implode(',', array_keys($attributes)), 'safe', 'on' => 'search');

		return $rules;
	}
}
