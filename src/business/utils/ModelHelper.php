<?php
namespace Blocks;

/**
 *
 */
class ModelHelper
{
	/*
	 * Gets the property type.
	 *
	 * @static
	 * @param mixed $config
	 * @return string
	 */
	public static function getPropertyType($config)
	{
		return is_string($config) ? $config : (isset($config['type']) ? $config['type'] : (isset($config[0]) ? $config[0] : PropertyType::Varchar));
	}

	/**
	 * Returns the rules array used by CActiveRecord.
	 *
	 * @static
	 * @param array $properties
	 * @param array $indexes
	 * @return array
	 */
	public static function createRules($properties, $indexes = array())
	{
		$rules = array();

		$uniques = array();
		$required = array();
		$emails = array();
		$urls = array();
		$strictLengths = array();
		$minLengths = array();
		$maxLengths = array();

		$integerTypes = array(PropertyType::Number, PropertyType::TinyInt, PropertyType::SmallInt, PropertyType::MediumInt, PropertyType::Int, PropertyType::BigInt);
		$numberTypes = $integerTypes;
		$numberTypes[] = PropertyType::Decimal;

		foreach ($properties as $name => $config)
		{
			$type = static::getPropertyType($config);

			// Catch handles, email addresses, languages and URLs before running normalizePropertyConfig, since 'type' will get changed to VARCHAR
			if ($type == PropertyType::Handle)
			{
				$reservedWords = isset($config['reservedWords']) ? ArrayHelper::stringToArray($config['reservedWords']) : array();
				$rules[] = array($name, 'Blocks\HandleValidator', 'reservedWords' => $reservedWords);
			}

			if ($type === PropertyType::UnixTimeStamp)
				$rules[] = array($name, 'Blocks\DateTimeValidator');

			if ($type == PropertyType::Language)
				$rules[] = array($name, 'Blocks\LanguageValidator');

			if ($type == PropertyType::Email)
				$emails[] = $name;

			if ($type == PropertyType::Url)
				$urls[] = $name;

			// Remember if it's a license key
			$isLicenseKey = ($type == PropertyType::LicenseKey);

			$config = DbHelper::normalizePropertyConfig($config);

			// Uniques
			if (!empty($config['unique']))
				$uniques[] = $name;

			// Only enforce 'required' validation if there's no default value
			if (isset($config['required']) && $config['required'] === true && !isset($config['default']))
				$required[] = $name;

			// Numbers
			if (in_array($config['type'], $numberTypes) && $type !== PropertyType::UnixTimeStamp)
			{
				$rule = array($name, 'Blocks\LocaleNumberValidator');

				if (isset($config['min']) && is_numeric($config['min']))
					$rule['min'] = $config['min'];

				if (isset($config['max']) && is_numeric($config['max']))
					$rule['max'] = $config['max'];

				if (($config['type'] == PropertyType::Number && empty($config['decimals'])) || in_array($config['type'], $integerTypes))
					$rule['integerOnly'] = true;

				$rules[] = $rule;
			}

			// Enum property values
			if ($config['type'] == PropertyType::Enum)
			{
				$values = ArrayHelper::stringToArray($config['values']);
				$rules[] = array($name, 'in', 'range' => $values);
			}

			// License keys' length=36 is redundant in the context of validation, since matchPattern already enforces 36 chars
			if ($isLicenseKey)
				unset($config['length']);

			// Strict, min, and max lengths
			if (isset($config['length']) && is_numeric($config['length']))
				$strictLengths[(string)$config['length']][] = $name;
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

		// Catch any unique indexes
		foreach ($indexes as $config)
		{
			if (!empty($config['unique']))
			{
				$columns = ArrayHelper::stringToArray($config['columns']);

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
			foreach ($strictLengths as $strictLength => $propertyNames)
			{
				$rules[] = array(implode(',', $propertyNames), 'length', 'is' => (int)$strictLength);
			}
		}

		if ($minLengths)
		{
			foreach ($minLengths as $minLength => $propertyNames)
			{
				$rules[] = array(implode(',', $propertyNames), 'length', 'min' => (int)$minLength);
			}
		}

		if ($maxLengths)
		{
			foreach ($maxLengths as $maxLength => $propertyNames)
			{
				$rules[] = array(implode(',', $propertyNames), 'length', 'max' => (int)$maxLength);
			}
		}

		$rules[] = array(implode(',', array_keys($properties)), 'safe', 'on' => 'search');

		return $rules;
	}
}
