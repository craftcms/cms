<?php
namespace Blocks;

/**
 * Widget template variable
 */
class WidgetVariable extends ComponentVariable
{
	/**
	 * Returns the widget's title.
	 *
	 * @return string
	 */
	public function title()
	{
		return $this->component->getTitle();
	}
}
