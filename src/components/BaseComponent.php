<?php
namespace Blocks;

/**
 * Component base class
 * Extended by BasePlugin, BaseWidget, BaseBlock, etc.
 */
abstract class BaseComponent extends \CComponent
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

	private $_classHandle;
	private $_settings;

	public function init(){}

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
	protected function defineSettings()
	{
		return array();
	}

	/**
	 * Gets the settings.
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
			$this->_settings = new Model($this->defineSettings());
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
