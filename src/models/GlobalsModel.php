<?php
namespace Blocks;

/**
 * Globals model class
 */
class GlobalsModel extends BaseElementModel
{
	protected $elementType = ElementType::Globals;

	/**
	 * Only one set of globals, so just return "Globals" as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Blocks::t('Globals');
	}
}
