<?php
namespace Craft;

/**
 * Component template variable class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
abstract class BaseComponentTypeVariable
{
	// Properties
	// =========================================================================

	/**
	 * @var BaseComponentType
	 */
	protected $component;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param BaseComponentType $component
	 *
	 * @return BaseComponentTypeVariable
	 */
	public function __construct($component)
	{
		$this->component = $component;
	}

	/**
	 * Use the component's name as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->component->getName();
	}

	/**
	 * Mass-populates instances of this class with a given set of models.
	 *
	 * @param array $models
	 *
	 * @return array
	 */
	public static function populateVariables($models)
	{
		return VariableHelper::populateVariables($models, get_called_class());
	}

	/**
	 * Returns the component's class handle.
	 *
	 * @return string
	 */
	public function getClassHandle()
	{
		return $this->component->getClassHandle();
	}

	/**
	 * Returns whether this component should be selectable when choosing a component of this type.
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return $this->component->isSelectable();
	}

	/**
	 * Returns the component's name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->component->getName();
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return $this->component->getSettingsHtml();
	}
}
