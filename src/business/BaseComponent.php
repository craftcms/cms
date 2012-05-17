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
	 * @var Model
	 */
	public $record;

	/**
	 * The type of component, e.g. "Plugin", "Widget", or "Block"
	 * Defined by the component type's base class.
	 * @access protected
	 * @var string
	 */
	protected $componentType;

	private $_classHandle;

	/**
	 * Get the class name, sans namespace.
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
	 * Getter
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
