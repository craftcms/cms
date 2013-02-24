<?php
namespace Craft;

/**
 * Globals element type
 */
class GlobalsElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Globals');
	}

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param ElementModel $entry
	 * @return string|null
	 */
	public function getCpEditUriForElement(ElementModel $entry)
	{
		return 'content/globals';
	}
}
