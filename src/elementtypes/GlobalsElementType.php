<?php
namespace Blocks;

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
		return Blocks::t('Globals');
	}

	/**
	 * Returns the CP edit URI for a given element.
	 *
	 * @param BaseElementModel $element
	 * @return string|null
	 */
	public function getCpEditUriForElement(BaseElementModel $element)
	{
		return 'content/globals';
	}
}
