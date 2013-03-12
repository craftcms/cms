<?php
namespace Craft;

/**
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the element type's link settings HTML.
	 *
	 * @return string
	 */
	public function getLinkSettingsHtml()
	{
		return $this->component->getLinkSettingsHtml();
	}
}
