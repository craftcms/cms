<?php
namespace Blocks;

/**
 * Component base class
 * Extended by BasePlugin, BaseWidget, BaseBlock, etc.
 */
abstract class BaseComponent extends BaseApplicationComponent
{
	/**
	 * The model instance associated with this component.
	 */
	public $model;

	/**
	 * The type of component, e.g. "Plugin", "Widget", or "Block"
	 * Defined by the component type's base class.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType;

	private $_classHandle;
	private $_settings;

	/**
	 * Returns the component’s name.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getName();

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
	 * Gets the settings model.
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

	/**
	 * Sets the setting values.
	 *
	 * @param array $values
	 */
	public function setSettings($values)
	{
		if ($values)
		{
			$this->getSettings()->setAttributes($values);
		}
	}

	/**
	 * Preprocesses the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function preprocessSettings($settings)
	{
		return $settings;
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
	 * Get the class name, sans namespace.
	 *
	 * @return string
	 */
	public function getClassHandle()
	{
		if (!isset($this->_classHandle))
		{
			// Chop off the namespace
			$classHandle = substr(get_class($this), strlen(__NAMESPACE__) + 1);

			// Chop off the class suffix
			$suffixLength = strlen($this->componentType);

			if (substr($classHandle, -$suffixLength) == $this->componentType)
			{
				$classHandle = substr($classHandle, 0, -$suffixLength);
			}

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}
}
