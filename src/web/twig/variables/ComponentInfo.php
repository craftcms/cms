<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\base\ComponentInterface;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\SavableComponentInterface;
use Exception;
use yii\base\ErrorHandler;

/**
 * ComponentInfo represents a component class, making information about it available to the templates.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ComponentInfo
{
	// Properties
	// =========================================================================

	/**
	 * @var ComponentInterface
	 */
	protected $component;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string|ComponentInterface $component The component, or its class name, that this object should provide info about
	 */
	public function __construct($component)
	{
		$this->component = $component;
	}

	/**
	 * Use the component’s display name as its string representation.
	 *
	 * @return string The component’s display name
	 */
	public function __toString()
	{
		try
		{
			return $this->getDisplayName();
		}
		catch (Exception $e)
		{
			ErrorHandler::convertExceptionToError($e);
		}
	}

	/**
	 * Returns whether the component is invalid
	 *
	 * @return boolean
	 */
	public function getIsInvalid()
	{
		return (is_object($this->component) && $this->component instanceof InvalidComponentInterface);
	}

	/**
	 * Returns the component’s class name.
	 *
	 * @return string
	 */
	public function getClassName()
	{
		$component = $this->component;

		if ($this->getIsInvalid())
		{
			/** @var InvalidComponentInterface $component */
			return $component->getType();
		}
		else
		{
			return $component::className();
		}
	}

	/**
	 * Returns the component’s display name.
	 *
	 * @return string
	 */
	public function getDisplayName()
	{
		$component = $this->component;

		if ($this->getIsInvalid())
		{
			/** @var InvalidComponentInterface $component */
			$classNameParts = explode('\\', $component->getType());
			$displayName = array_pop($classNameParts);
			return $displayName;
		}
		else
		{
			return $component::displayName();
		}
	}

	/**
	 * Returns the component’s class handle.
	 *
	 * @return string
	 */
	public function getClassHandle()
	{
		$component = $this->component;

		if ($this->getIsInvalid())
		{
			/** @var InvalidComponentInterface $component */
			$classNameParts = explode('\\', $component->getType());
			$handle = array_pop($classNameParts);
			return strtolower($handle);
		}
		else
		{
			return $component::classHandle();
		}
	}

	/**
	 * Returns whether the component should be selectable in component Type selects.
	 *
	 * @return boolean whether the component should be selectable in component Type selects.
	 */
	public function getIsSelectable()
	{
		$component = $this->component;

		if (!is_subclass_of($component, 'craft\app\base\SavableComponentInterface') || $this->getIsInvalid())
		{
			return false;
		}
		else
		{
			/** @var SavableComponentInterface $component */
			return $component::isSelectable();
		}
	}
}
