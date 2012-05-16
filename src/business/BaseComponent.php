<?php
namespace Blocks;

/**
 * Component base class
 * Extended by BasePlugin, BaseWidget, BaseBlock, etc.
 */
abstract class BaseComponent extends \CApplicationComponent
{
	/**
	 * The type of component, e.g. "Plugin", "Widget", or "Block"
	 * Defined by the component type's base class.
	 * @access protected
	 * @var string
	 */
	protected $componentType;

	private $_classHandle;
	private $_record;

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
	 * Returns the component's AR record.
	 * @return Model
	 */
	public function getRecord()
	{
		if (!isset($this->_record))
		{
			$class = __NAMESPACE__.'\\'.$this->componentType;
			$this->_record = new $class;
			$this->_record->class = $this->getClassHandle();
		}
		return $this->_record;
	}

	/**
	 * Sets the component's AR record.
	 * @param Model $record
	 */
	public function setRecord($record)
	{
		$this->_record = $record;
	}

	/**
	 * Getter
	 */
	function __get($name)
	{
		if (isset($this->getRecord()->$name))
			return $this->getRecord()->$name;
		else
			return parent::__get($name);
	}

}
