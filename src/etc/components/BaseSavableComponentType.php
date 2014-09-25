<?php
namespace Craft;

/**
 * Base savable component class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.components
 * @since     1.0
 */
abstract class BaseSavableComponentType extends BaseComponentType implements ISavableComponentType
{
	// Properties
	// =========================================================================

	/**
	 * The model instance associated with the current component instance.
	 *
	 * @var BaseModel
	 */
	public $model;

	/**
	 * @var
	 */
	private $_settings;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ISavableComponentType::getSettings()
	 *
	 * @return BaseModel
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
		{
			$this->_settings = $this->getSettingsModel();
		}

		return $this->_settings;
	}

	/**
	 * @inheritDoc ISavableComponentType::setSettings()
	 *
	 * @param array|BaseModel $values
	 *
	 * @return null
	 */
	public function setSettings($values)
	{
		if ($values)
		{
			if ($values instanceof BaseModel)
			{
				$this->_settings = $values;
			}
			else
			{
				$this->getSettings()->setAttributes($values);
			}
		}
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * @inheritDoc ISavableComponentType::prepSettings()
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		return $settings;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the settings model.
	 *
	 * @return BaseModel
	 */
	protected function getSettingsModel()
	{
		return new Model($this->defineSettings());
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array();
	}
}
