<?php
namespace Blocks;

/**
 * Base component model class
 *
 * Used for transporting component data throughout the system.
 *
 * @abstract
 */
abstract class BaseComponentModel extends BaseModel
{
	private $_settingErrors = array();

	public function defineAttributes()
	{
		$attributes['id'] = array(AttributeType::Number);
		$attributes['type'] = array(AttributeType::String);
		$attributes['settings'] = array(AttributeType::Mixed);

		return $attributes;
	}

	/**
	 * Returns whether there are setting errors.
	 *
	 * @return bool
	 */
	public function hasSettingErrors()
	{
		return !empty($this->_settingErrors);
	}

	/**
	 * Returns the errors for all settings attributes.
	 *
	 * @return array
	 */
	public function getSettingErrors()
	{
		return $this->_settingErrors;
	}

	/**
	 * Adds a new error to the specified setting attribute.
	 *
	 * @param string $attribute
	 * @param string $error
	 */
	public function addSettingsError($attribute,$error)
	{
		$this->_settingErrors[$attribute][] = $error;
	}

	/**
	 * Adds a list of settings errors.
	 *
	 * @param array $errors
	 */
	public function addSettingErrors($errors)
	{
		foreach ($errors as $attribute => $error)
		{
			if (is_array($error))
			{
				foreach ($error as $e)
				{
					$this->addSettingsError($attribute, $e);
				}
			}
			else
			{
				$this->addSettingsError($attribute, $error);
			}
		}
	}
}
