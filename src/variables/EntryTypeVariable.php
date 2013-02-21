<?php
namespace Blocks;

/**
 * Entry type template variable
 */
class EntryTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the entry type's link settings HTML.
	 *
	 * @return string
	 */
	public function getLinkSettingsHtml()
	{
		return $this->component->getLinkSettingsHtml();
	}
}
