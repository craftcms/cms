<?php
namespace Craft;

/**
 * Component template variable class
 *
 * @abstract
 * @package craft.app.validators
 */
abstract class BaseComponentTypeVariable
{
	protected $component;

	/**
	 * Constructor
	 *
	 * @param BaseComponentType $component
	 */
	function __construct($component)
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

	/**
	 * Mass-populates instances of this class with a given set of models.
	 *
	 * @static
	 * @param array $models
	 * @return array
	 */
	public static function populateVariables($models)
	{
		return VariableHelper::populateVariables($models, get_called_class());
	}
}
