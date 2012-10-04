<?php
namespace Blocks;

/**
 * Component template variable class
 *
 * @abstract
 */
abstract class BaseComponentVariable
{
	protected $component;

	/**
	 * Constructor
	 *
	 * @param BaseComponent $component
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
	public function classHandle()
	{
		return $this->component->getClassHandle();
	}

	/**
	 * Returns the component's name.
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->component->getName();
	}

	/**
	 * Returns the component's settings.
	 *
	 * @param array|null $settings
	 * @return string
	 */
	public function settings($settings = null)
	{
		$this->component->setSettings($settings);
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
