<?php
namespace Craft;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseSavableComponentType
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'Widget';

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
