<?php
namespace Craft;

/**
 * Link functions
 */
class LinksVariable
{
	/**
	 * Returns all linkable element types.
	 *
	 * @return array
	 */
	public function getAllLinkableElementTypes()
	{
		$elementTypes = craft()->links->getAllLinkableElementTypes();
		return ElementTypeVariable::populateVariables($elementTypes);
	}

	/**
	 * Returns a linkable element type.
	 *
	 * @param string $class
	 * @return ElementTypeVariable|null
	 */
	public function getLinkableElementType($class)
	{
		$elementType = craft()->links->getLinkableElementType($class);

		if ($elementType)
		{
			return new ElementTypeVariable($elementType);
		}
	}
}
