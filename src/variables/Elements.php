<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Class Elements variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Elements
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
		$elementTypes = [];

		foreach (Craft::$app->elements->getAllElementTypes() as $classHandle => $elementType)
		{
			$elementTypes[$classHandle] = new ElementType($elementType);
		}

		return $elementTypes;
	}

	/**
	 * Returns an element type.
	 *
	 * @param string $class
	 *
	 * @return ElementType|null
	 */
	public function getElementType($class)
	{
		$elementType = Craft::$app->elements->getElementType($class);

		if ($elementType)
		{
			return new ElementType($elementType);
		}
	}
}
