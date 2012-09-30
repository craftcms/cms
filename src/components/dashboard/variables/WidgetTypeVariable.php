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
	public function title()
	{
		return $this->component->getTitle();
	}
}
