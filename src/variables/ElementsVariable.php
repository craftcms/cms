<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Class ElementsVariable
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
