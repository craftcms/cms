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
			if (isset($config[0]))
			{
				$config['type'] = $config[0];
				unset($config[0]);
			}
			else
			{
				$config['type'] = AttributeType::String;
			}
		}

		// Merge in the default attribute + column configs
		if (isset(static::$attributeTypeDefaults[$config['type']]))
			$config = array_merge(static::$attributeTypeDefaults[$config['type']], $config);

		// Set the column type, min, and max values for Number attributes
		if ($config['type'] == AttributeType::Number && !isset($config['column']))
		{
			$numberConfig = DbHelper::getNumberColumnConfig($config['min'], $config['max'], $config['decimals']);
			$config = array_merge($config, $numberConfig);
		}

		// Add in DB column-specific settings
		if (isset($config['column']))
		{
			if (isset(DbHelper::$columnTypeDefaults[$config['column']]))
				$config = array_merge(DbHelper::$columnTypeDefaults[$config['column']], $config);

			// Add unsigned, min, and max settings to number columns
			if (isset(DbHelper::$intColumnSizes[$config['column']]))
			{
				if (!isset($config['unsigned']))
					$config['unsigned'] = (isset($config['min']) && $config['min'] >= 0);

				$maxSize = DbHelper::$intColumnSizes[$config['column']];
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

		$uniqueAttributes = array();
		$requiredAttributes = array();
		$emailAttributes = array();
		$urlAttributes = array();
		$strictLengthAttributes = array();
		$minLengthAttributes = array();
		$maxLengthAttributes = array();

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
					$emailAttributes[] = $name;
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

					if ($config['min'])
						$rule['min'] = $config['min'];

					if ($config['max'])
						$rule['max'] = $config['max'];

					if (!$config['decimals'])
						$rule['integerOnly'] = true;

					$rules[] = $rule;
					break;
				}

				case AttributeType::Url:
				{
					$urlAttributes[] = $name;
					break;
				}
			}

			// Remember if it's a license key
			$isLicenseKey = ($config['type'] == AttributeType::LicenseKey);

			$config = ModelHelper::normalizeAttributeConfig($config);

			// Uniques
			if (!empty($config['unique']))
				$uniqueAttributes[] = $name;

			// Required
			if ($config['type'] != AttributeType::Bool && !empty($config['required']))
				$requiredAttributes[] = $name;

			// License keys' length=36 is redundant in the context of validation, since matchPattern already enforces 36 chars
			if ($isLicenseKey)
				unset($config['length']);

			// Lengths
			if (isset($config['length']) && is_numeric($config['length']))
			{
				$strictLengthAttributes[(string)$config['length']][] = $name;
			}
			else
			{
				// Only worry about min- and max-lengths if a strict length isn't set
				if (isset($config['minLength']) && is_numeric($config['minLength']))
					$minLengthAttributes[(string)$config['minLength']][] = $name;

				if (isset($config['maxLength']) && is_numeric($config['maxLength']))
					$maxLengthAttributes[(string)$config['maxLength']][] = $name;
			}

			// Compare with other attributes
			if (isset($config['compare']))
			{
				$comparisons = ArrayHelper::stringToArray($config['compare']);
				foreach ($comparisons as $comparison)
				{
					if (preg_match('/^(==|=|!=|>=|>|<=|<)\s*\b(.*)$/', $comparison, $match))
						$rules[] = array($name, 'compare', 'compareAttribute' => $match[2], 'operator' => $match[1]);
				}
			}

			// Regex pattern matching
			if (!empty($config['matchPattern']))
				$rules[] = array($name, 'match', 'pattern' => $config['matchPattern']);
		}

		// If this is a BaseRecord instance, catch any required BELONGS_TO relations and unique/required indexes
		if ($model instanceof BaseRecord)
		{
			foreach ($model->getBelongsToRelations() as $name => $config)
			{
				if (!empty($config['required']))
					$requiredAttributes[] = $name;
			}

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
							$uniqueAttributes[] = $columns[0];
						}
						else
						{
							$initialColumn = array_shift($columns);
							$rules[] = array($initialColumn, 'Blocks\CompositeUniqueValidator', 'with' => implode(',', $columns));
						}
					}

					if ($required)
					{
						$requiredAttributes = array_merge($requiredAttributes, $columns);
					}
				}
			}
		}

		if ($uniqueAttributes)
			$rules[] = array(implode(',', $uniqueAttributes), 'unique');

		if ($requiredAttributes)
			$rules[] = array(implode(',', $requiredAttributes), 'required');

		if ($emailAttributes)
			$rules[] = array(implode(',', $emailAttributes), 'email');

		if ($urlAttributes)
			$rules[] = array(implode(',', $urlAttributes), 'url', 'defaultScheme' => 'http');

		if ($strictLengthAttributes)
		{
			foreach ($strictLengthAttributes as $strictLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'is' => (int)$strictLength);
			}
		}

		if ($minLengthAttributes)
		{
			foreach ($minLengthAttributes as $minLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'min' => (int)$minLength);
			}
		}

		if ($maxLengthAttributes)
		{
			foreach ($maxLengthAttributes as $maxLength => $attributeNames)
			{
				$rules[] = array(implode(',', $attributeNames), 'length', 'max' => (int)$maxLength);
			}
		}

		$rules[] = array(implode(',', array_keys($attributes)), 'safe', 'on' => 'search');

		return $rules;
	}

	/**
	 * @static
	 * @access private
	 * @var array
	 */
	private static $_comparisonOperators = array('==|=|!=|>=|>|<=|<');
}
