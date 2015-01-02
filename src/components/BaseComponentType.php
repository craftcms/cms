<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\components;

use yii\base\Object;

/**
 * Base component base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseComponentType extends Object implements ComponentTypeInterface
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType;

	/**
	 * The component's class handle.
	 *
	 * @var string
	 */
	private $_classHandle;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getClassHandle();
	}

	/**
	 * @inheritDoc ComponentTypeInterface::getClassHandle()
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
	 * @inheritDoc ComponentTypeInterface::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return true;
	}
}
