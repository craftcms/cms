<?php
namespace Blocks;

/**
 * Component base class
 * Extended by BasePlugin, BaseWidget, BaseBlock, etc.
 */
abstract class BaseComponent extends ApplicationComponent
{
	/**
	 * The AR record associated with this instance.
	 *
	 * @var Model
	 */
	public $record;

	/**
	 * The type of component, e.g. "Plugin", "Widget", or "Block"
	 * Defined by the component type's base class.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType;

	/**
	 * The column that the record settings will be stored in.
	 *
	 * @access protected
	 * @var string
	 */
	protected $settingsColumn;

	private $_classHandle;
	private $_settings;

	/**
	 * Returns the name of the component.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Use the component's type as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	public function defineSettings()
	{
		return array();
	}

	/**
	 * Gets the settings.
	 *
	 * @return BaseModel
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
		{
			$this->_settings = new Model($this->defineSettings());

			// If a record is set, fill in the saved settings
			if (isset($this->record))
			{
				$settings = $this->record->getAttribute($this->settingsColumn);
				$this->_settings->setAttributes($settings);
			}
		}
		return $this->_settings;
	}

	/**
	 * Sets the setting values.
	 *
	 * @param array $values
	 */
	public function setSettings($values)
	{
		$this->getSettings()->setAttributes($values);
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
				$classHandle = substr($classHandle, 0, -$suffixLength);

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}
}
