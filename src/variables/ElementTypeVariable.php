<?php
namespace Craft;

/**
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Return a key/label list of the element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		return $this->component->getSources();
	}
}
