<?php
namespace Craft;

/**
 * Base component base class.
 *
 * @package craft.app.etc.components
 */
abstract class BaseComponentType extends BaseApplicationComponent implements IComponentType
{
	/**
	 * @var string The type of component, e.g. "Plugin", "Widget", or "Field". Defined by the component type's base class.
	 */
	protected $componentType;

	/**
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
			$classHandle = mb_substr(get_class($this), mb_strlen(__NAMESPACE__) + 1);

			// Chop off the class suffix
			$suffixLength = mb_strlen($this->componentType);

			if (mb_substr($classHandle, -$suffixLength) == $this->componentType)
			{
				$classHandle = mb_substr($classHandle, 0, -$suffixLength);
			}

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}

	/**
	 * Returns whether this component should be selectable when choosing a component of this type.
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return true;
	}
}
