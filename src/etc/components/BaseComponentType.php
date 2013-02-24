<?php
namespace Blocks;

/**
 * Base component base class
 */
abstract class BaseComponentType extends BaseApplicationComponent implements IComponentType
{
	/**
	 * @access protected
	 * @var string The type of component, e.g. "Plugin", "Widget", or "Block". Defined by the component type's base class.
	 */
	protected $componentType;

	/**
	 * @access private
	 * @var string The component's class handle.
	 */
	private $_classHandle;

	/**
	 * Returns the component’s name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getClassHandle();
	}

	/**
	 * Get the class name, sans namespace and suffix.
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
