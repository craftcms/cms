<?php
namespace Craft;

/**
 *
 */
class ElementsVariable
{
	/**
	 * Returns all installed element types.
	 *
	 * @return array
	 */
	public function getAllElementTypes()
	{
		$elementTypes = array();

		foreach (craft()->elements->getAllElementTypes() as $elementType)
		{
			$elementTypes[] = new ElementTypeVariable($elementType);
		}

		return $elementTypes;
	}

	/**
	 * Returns an element type.
	 *
	 * @param string $class
	 * @return ElementTypeVariable|null
	 */
	public function getElementType($class)
	{
		$elementType = craft()->elements->getElementType($class);

		if ($elementType)
		{
			return new ElementTypeVariable($elementType);
		}
	}
}
