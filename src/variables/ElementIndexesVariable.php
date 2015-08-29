<?php
namespace Craft;

/**
 * Class ElementIndexesVariable
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     2.5
 */
class ElementIndexesVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the element index sources in the custom groupings/order.
	 *
	 * @param string $elementTypeClass The element type class
	 * @param string $context          The context
	 *
	 * @return array
	 */
	public function getSources($elementTypeClass, $context = 'index')
	{
		return craft()->elementIndexes->getSources($elementTypeClass, $context);
	}
}
