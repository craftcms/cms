<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * BaseComponentModel class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseComponentModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_settingErrors = [];

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether this is a new component.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * Returns whether there are setting errors.
	 *
	 * @param string|null $attribute
	 *
	 * @return bool
	 */
	public function hasSettingErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_settingErrors !== [];
		}
		else
		{
			return isset($this->_settingErrors[$attribute]);
		}
	}

	/**
	 * Returns the errors for all settings attributes.
	 *
	 * @param string|null $attribute
	 *
	 * @return array
	 */
	public function getSettingErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_settingErrors;
		}
		else
		{
			return isset($this->_settingErrors[$attribute]) ? $this->_settingErrors[$attribute] : [];
		}
	}

	/**
	 * Adds a new error to the specified setting attribute.
	 *
	 * @param string $attribute
	 * @param string $error
	 *
	 * @return null
	 */
	public function addSettingsError($attribute,$error)
	{
		$this->_settingErrors[$attribute][] = $error;
	}

	/**
	 * Adds a list of settings errors.
	 *
	 * @param array $errors
	 *
	 * @return null
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'id'       => AttributeType::Number,
			'type'     => [AttributeType::String],
			'settings' => AttributeType::Mixed,
		];
	}
}
