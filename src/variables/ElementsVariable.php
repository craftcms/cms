<?php
namespace Craft;

/**
 * Class ElementsVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class ElementsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all installed element types.
	 *
	 * @return array
	 */
	public function getAllElementTypes()
	{
		$elementTypes = array();

		foreach (craft()->elements->getAllElementTypes() as $classHandle => $elementType)
		{
			$elementTypes[$classHandle] = new ElementTypeVariable($elementType);
		}

		return $elementTypes;
	}

	/**
	 * Returns an element type.
	 *
	 * @param string $class
	 *
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
