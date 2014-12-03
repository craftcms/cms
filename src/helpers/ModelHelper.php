<?php
namespace Craft;

/**
 * Class ModelHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class ModelHelper
{
	// Properties
	// =========================================================================

	/**
	 * The default attribute configs.
	 *
	 * @var array
	 */
	public static $attributeTypeDefaults = array(
		AttributeType::Mixed      => array('model' => null, 'column' => ColumnType::Text),
		AttributeType::Bool       => array('maxLength' => 1, 'default' => false, 'required' => true, 'column' => ColumnType::TinyInt, 'unsigned' => true),
		AttributeType::ClassName  => array('maxLength' => 150, 'column' => ColumnType::Varchar),
		AttributeType::DateTime   => array('column' => ColumnType::DateTime),
		AttributeType::Email      => array('minLength' => 5, 'column' => ColumnType::Varchar),
		AttributeType::Enum       => array('values' => array(), 'column' => ColumnType::Enum),
		AttributeType::Handle     => array('maxLength' => 255, 'reservedWords' => 'id,dateCreated,dateUpdated,uid,title', 'column' => ColumnType::Varchar),
		AttributeType::Locale     => array('column' => ColumnType::Locale),
		AttributeType::Name       => array('maxLength' => 255, 'column' => ColumnType::Varchar),
		AttributeType::Number     => array('min' => null, 'max' => null, 'decimals' => 0),
		AttributeType::SortOrder  => array('column' => ColumnType::TinyInt),
		AttributeType::Template   => array('maxLength' => 500, 'column' => ColumnType::Varchar),
		AttributeType::Url        => array('maxLength' => 255, 'column' => ColumnType::Varchar),
	);

	/**
	 * Integer column sizes.
	 *
	 * @var array
	 */
	private static $_intColumnSizes = array(
		ColumnType::TinyInt   => 128,
		ColumnType::SmallInt  => 32768,
		ColumnType::MediumInt => 8388608,
		ColumnType::Int       => 2147483648,
		ColumnType::BigInt    => 9223372036854775808
	);

	// Public Methods
	// =========================================================================

	/**
	 * Normalizes an attribute's config.
	 *
	 * Attributes can be defined in 3 ways:
	 *
	 * 1. AttributeType::TypeName
	 * 2. array(AttributeType::TypeName [, 'other' => 'settings' ... ] )
	 * 3. array('type' => AttributeType::TypeName [, 'other' => 'settings' ... ] )
	 *
	 * This function normalizes on the 3rd, and merges in the default config settings for the attribute type, merges in
	 * the default column settings if 'column' is set, and sets the 'unsigned', 'min', and 'max' values for integers.
	 *
	 * @param string|array $config
	 *
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
		{
			$config = array_merge(static::$attributeTypeDefaults[$config['type']], $config);
		}

		// Set the column type, min, and max values for Number attributes
		if ($config['type'] == AttributeType::Number && !isset($config['column']))
		{
			$numberConfig = static::getNumberAttributeConfig($config['min'], $config['max'], $config['decimals']);
			$config = array_merge($config, $numberConfig);
		}

		// Add in DB column-specific settings
		if ($config['type'] != AttributeType::DateTime && isset($config['column']))
		{
			if (isset(DbHelper::$columnTypeDefaults[$config['column']]))
			{
				$config = array_merge(DbHelper::$columnTypeDefaults[$config['column']], $config);
			}

			// Add unsigned, min, and max settings to number columns
			if (isset(static::$_intColumnSizes[$config['column']]))
			{
				if (!isset($config['unsigned']))
				{
					$config['unsigned'] = (isset($config['min']) && $config['min'] >= 0);
				}

				$maxSize = static::$_intColumnSizes[$config['column']];
				$minMin = $config['unsigned'] ? 0 : -$maxSize;
				$maxMax = ($config['unsigned'] ? $maxSize * 2 : $maxSize) - 1;

				if (!isset($config['min']) || $config['min'] < $minMin)
				{
					$config['min'] = $minMin;
				}

				if (!isset($config['max']) || $config['max'] > $maxMax)
				{
					$config['max'] = $maxMax;
				}
			}
		}

		return $config;
	}

	/**
	 * Returns a number attribute config, taking the min, max, and number of decimal points into account.
	 *
	 * @param int $min
	 * @param int $max
	 * @param int $decimals
	 *
	 * @return array
	 */
	public static function getNumberAttributeConfig($min = null, $max = null, $decimals = null)
	{
		$config = array();

		// Normalize the arguments
		$config['type'] = AttributeType::Number;
		$config['min'] = is_numeric($min) ? $min : -static::$_intColumnSizes[ColumnType::Int];
		$config['max'] = is_numeric($max) ? $max : static::$_intColumnSizes[ColumnType::Int]-1;
		$config['decimals'] = is_numeric($decimals) && $decimals > 0 ? intval($decimals) : 0;

		// Unsigned?
		$config['unsigned'] = ($config['min'] >= 0);

		// Figure out the max length
		$maxAbsSize = intval($config['unsigned'] ? $config['max'] : max(abs($config['min']), abs($config['max'])));
		$config['length'] = ($maxAbsSize ? mb_strlen($maxAbsSize) : 0) + $config['decimals'];

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
					{
						break;
					}
				}
				else
				{
					if ($config['min'] >= -$size && $config['max'] < $size)
					{
						break;
					}
				}
			}

			$config['column'] = $colType;
		}

		return $config;
	}

	/**
	 * Populates any default values that are defined for a model.
	 *
	 * @param \CModel $model
	 *
	 * @return null
	 */
	public static function populateAttributeDefaults(\CModel $model)
	{
		foreach ($model->getAttributeConfigs() as $name => $config)
		{
			if (isset($config['default']))
			{
				$model->setAttribute($name, $config['default']);
			}
		}
	}

	/**
	 * Returns the rules array used by CModel.
	 *
	 * @param \CModel $model
	 *
	 * @return array
	 */
	public static function getRules(\CModel $model)
	{
		$rules = array();

		$uniqueAttributes = array();
		$uniqueRequiredAttributes = array();
		$requiredAttributes = array();
		$emailAttributes = array();
		$urlAttributes = array();
		$urlFormatAttributes = array();
		$uriAttributes = array();
		$strictLengthAttributes = array();
		$minLengthAttributes = array();
		$maxLengthAttributes = array();

		$attributes = $model->getAttributeConfigs();

		foreach ($attributes as $name => $config)
		{
			switch ($config['type'])
			{
				case AttributeType::DateTime:
				{
					$rules[] = array($name, 'Craft\DateTimeValidator');
					break;
				}

				case AttributeType::Email:
				{
					$emailAttributes[] = $name;
					break;
				}

				case AttributeType::Enum:
				{
					$rules[] = array($name, 'in', 'range' => ArrayHelper::stringToArray($config['values']));
					break;
				}

				case AttributeType::Handle:
				{
					$rules[] = array($name, 'Craft\HandleValidator', 'reservedWords' => ArrayHelper::stringToArray($config['reservedWords']));
					break;
				}

				case AttributeType::Locale:
				{
					$rules[] = array($name, 'Craft\LocaleValidator');
					break;
				}

				case AttributeType::Number:
				{
					$rule = array($name, 'numerical');

					if ($config['min'] !== null)
					{
						$rule['min'] = $config['min'];
					}

					if ($config['max'] !== null)
					{
						$rule['max'] = $config['max'];
					}

					if (!$config['decimals'])
					{
						$rule['integerOnly'] = true;
					}

					$rules[] = $rule;
					break;
				}

				case AttributeType::Url:
				{
					$urlAttributes[] = $name;
					break;
				}

				case AttributeType::UrlFormat:
				{
					$urlFormatAttributes[] = $name;
					break;
				}

				case AttributeType::Uri:
				{
					$uriAttributes[] = $name;
					break;
				}
			}

			// Uniques
			if (!empty($config['unique']))
			{
				if (empty($config['required']) && (isset($config['null']) && $config['null'] === false))
				{
					$uniqueRequiredAttributes[] = $name;
				}
				else
				{
					$uniqueAttributes[] = $name;
				}
			}

			// Required
			if ($config['type'] != AttributeType::Bool && !empty($config['required']))
			{
				$requiredAttributes[] = $name;
			}

			// Lengths
			if ($config['type'] != AttributeType::Number && $config['type'] != AttributeType::Mixed)
			{
				if (isset($config['length']) && is_numeric($config['length']))
				{
					$strictLengthAttributes[(string)$config['length']][] = $name;
				}
				else
				{
					// Only worry about min- and max-lengths if a strict length isn't set
					if (isset($config['minLength']) && is_numeric($config['minLength']))
					{
						$minLengthAttributes[(string)$config['minLength']][] = $name;
					}

					if (isset($config['maxLength']) && is_numeric($config['maxLength']))
					{
						$maxLengthAttributes[(string)$config['maxLength']][] = $name;
					}
				}
			}

			// Compare with other attributes
			if (isset($config['compare']))
			{
				$comparisons = ArrayHelper::stringToArray($config['compare']);
				foreach ($comparisons as $comparison)
				{
					if (preg_match('/^(==|=|!=|>=|>|<=|<)\s*\b(.*)$/', $comparison, $match))
					{
						$rules[] = array($name, 'compare', 'compareAttribute' => $match[2], 'operator' => $match[1], 'allowEmpty' => true);
					}
				}
			}

			// Regex pattern matching
			if (!empty($config['matchPattern']))
			{
				$rules[] = array($name, 'match', 'pattern' => $config['matchPattern']);
			}
		}

		// If this is a BaseRecord instance, catch any unique/required indexes. We don't validate required BELONGS_TO
		// relations because they mightnot get set until after validation.
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
							if (empty($attributes[$columns[0]]['required']) && (isset($attributes[$columns[0]]['null']) && $attributes[$columns[0]]['null'] === false))
							{
								$uniqueRequiredAttributes[] = $columns[0];
							}
							else
							{
								$uniqueAttributes[] = $columns[0];
							}
						}
						else
						{
							$initialColumn = array_shift($columns);
							$rules[] = array($initialColumn, 'Craft\CompositeUniqueValidator', 'with' => implode(',', $columns));
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
		{
			$rules[] = array(implode(',', $uniqueAttributes), 'unique');
		}

		if ($uniqueRequiredAttributes)
		{
			$rules[] = array(implode(',', $uniqueRequiredAttributes), 'unique', 'allowEmpty' => false);
		}

		if ($requiredAttributes)
		{
			$rules[] = array(implode(',', $requiredAttributes), 'required');
		}

		if ($emailAttributes)
		{
			$rules[] = array(implode(',', $emailAttributes), 'email');
		}

		if ($urlAttributes)
		{
			$rules[] = array(implode(',', $urlAttributes), 'Craft\UrlValidator', 'defaultScheme' => 'http');
		}

		if ($urlFormatAttributes)
		{
			$rules[] = array(implode(',', $urlFormatAttributes), 'Craft\UrlFormatValidator');
		}

		if ($uriAttributes)
		{
			$rules[] = array(implode(',', $uriAttributes), 'Craft\UriValidator');
		}

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
	 * Returns the attribute labels.
	 *
	 * @param \CModel $model
	 *
	 * @return array
	 */
	public static function getAttributeLabels(\CModel $model)
	{
		$labels = array();

		foreach ($model->getAttributeConfigs() as $name => $config)
		{
			if (isset($config['label']))
			{
				$labels[$name] = Craft::t($config['label']);
			}
		}

		return $labels;
	}

	/**
	 * Takes an attribute's config and value and "normalizes" them either for saving to db or sending across a web
	 * service.
	 *
	 * @param mixed $value
	 * @param bool  $jsonEncodeArrays
	 *
	 * @return int|mixed|null|string
	 */
	public static function packageAttributeValue($value, $jsonEncodeArrays = false)
	{
		if ($value instanceof \DateTime)
		{
			return DateTimeHelper::formatTimeForDb($value->getTimestamp());
		}

		if ($value instanceof BaseModel)
		{
			$attributes = $value->getAttributes(null, true);

			if ($value instanceof ElementCriteriaModel)
			{
				$attributes['__criteria__'] = $value->getElementType()->getClassHandle();
			}
			else
			{
				$attributes['__model__'] = get_class($value);
			}

			$value = $attributes;
		}

		if (is_array($value))
		{
			// Flatten each of its keys
			foreach ($value as $key => $val)
			{
				$value[$key] = static::packageAttributeValue($val);
			}

			if ($jsonEncodeArrays)
			{
				return JsonHelper::encode($value);
			}
			else
			{
				return $value;
			}
		}

		if (is_bool($value))
		{
			return $value ? 1 : 0;
		}

		return $value;
	}

	/**
	 * Searches an array for any flattened models, and expands them back to models.
	 *
	 * @param array $arr
	 *
	 * @return array|BaseModel
	 */
	public static function expandModelsInArray($arr)
	{
		foreach ($arr as $key => $val)
		{
			if (is_array($val))
			{
				$arr[$key] = static::expandModelsInArray($val);
			}
		}

		if (isset($arr['__criteria__']))
		{
			return craft()->elements->getCriteria($arr['__criteria__'], $arr);
		}

		if (isset($arr['__model__']))
		{
			$class = $arr['__model__'];
			$model = new $class();
			$model->setAttributes($arr);
			return $model;
		}

		return $arr;
	}
}
