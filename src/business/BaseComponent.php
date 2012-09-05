<?php
namespace Blocks;

/**
 * Component base class
 * Extended by BasePlugin, BaseWidget, BaseBlock, etc.
 */
abstract class BaseComponent extends \CApplicationComponent
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

	/**
	 * Returns the type of... whatever it is.
	 *
	 * @return string
	 */
	public function getType()
	{
		return '';
	}

	/**
	 * Use the component's type as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getType();
	}

	/**
	 * Returns the default block settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getDefaultSettings()
	{
		return array();
	}

	/**
	 * Gets the settings.
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
			$this->_settings = $this->getDefaultSettings();
		return $this->_settings;
	}

	/**
	 * Sets the settings.
	 *
	 * @param array $settings
	 */
	public function setSettings($settings)
	{
		if (!is_array($settings))
			$settings = array();
		$this->_settings = array_merge($this->getDefaultSettings(), $settings);
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

	/**
	 * Is Set?
	 *
	 * @param string $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (isset($this->record->$name))
			return true;
		else
			return parent::__isset($name);
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		if (isset($this->record->$name))
			return $this->record->$name;
		else
			return parent::__get($name);
	}
}
