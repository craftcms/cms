<?php
namespace Craft;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseSavableComponentType implements IWidget
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
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		return '<div style="margin: 0 -30px -30px;">' .
				'<img style="display: block; width: 100%;" src="'.UrlHelper::getResourceUrl('images/prg.jpg').'">' .
			'</div>';
	}
}
