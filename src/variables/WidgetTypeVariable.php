<?php
namespace Craft;

/**
 * Widget template variable
 */
class WidgetTypeVariable extends BaseComponentTypeVariable
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
	 * Returns the widget's colspan.
	 *
	 * @return int
	 */
	public function getColspan()
	{
		return $this->component->getColspan();
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		return $this->component->getBodyHtml();
	}
}
