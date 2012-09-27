<?php
namespace Blocks;

/**
 * Component template variable class
 */
class ComponentVariable
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
	 * Returns a ModelVariable instance for the component's record.
	 *
	 * @return ModelVariable
	 */
	public function record()
	{
		if ($this->component->record)
			return new ModelVariable($this->component->record);
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
}
