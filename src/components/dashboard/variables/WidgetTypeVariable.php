<?php
namespace Blocks;

/**
 * Widget template variable
 */
class WidgetTypeVariable extends BaseComponentVariable
{
	/**
	 * Returns the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->component->getTitle();
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml()
	{
		return $this->component->getBodyHtml();
	}
}
