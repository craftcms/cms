<?php
namespace Craft;

/**
 * Base savable component class.
 *
 * @abstract
 * @implements ISavableComponentType
 * @package craft.app.etc.components
 */
abstract class BaseSavableComponentType extends BaseComponentType implements ISavableComponentType
{
	/**
	 * @var BaseModel The model instance associated with the current component instance.
	 */
	public $model;

	/**
	 * @access private
	 * @var BaseModel The model representing the current component instance's settings.
	 */
	private $_settings;

	/**
	 * Gets the settings.
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
	 * Sets the setting values.
	 *
	 * @param array|BaseModel $values
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
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		return $settings;
	}

	/**
	 * Returns the settings model.
	 *
	 * @access protected
	 * @return BaseModel
	 */
	protected function getSettingsModel()
	{
		return new Model($this->defineSettings());
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array();
	}
}
