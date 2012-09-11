<?php
namespace Blocks;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseComponent implements IWidget
{
	protected $componentType = 'Widget';

	/**
	 * Returns the widget's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		// Default to the widget name
		return $this->getName();
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getBodyHtml();
}
