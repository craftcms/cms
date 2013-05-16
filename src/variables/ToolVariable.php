<?php
namespace Craft;

/**
 * Tool template variable
 */
class ToolVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return $this->component->getOptionsHtml();
	}
}
