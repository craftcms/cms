<?php
namespace Craft;

/**
 * Interface IEagerLoadingFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
interface IEagerLoadingFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns an array that maps source-to-target element IDs based on this custom field.
	 *
	 * This method aids in the eager-loading of elements when performing an element query. The returned array should
	 * contain two sub-keys:
	 *
	 * - `elementType` – indicating the type of sub-elements to eager-load (the element type class handle)
	 * - `map` – an array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
	 *
	 * @param BaseElementModel[] $sourceElements An array of the source elements
	 *
	 * @return array|false The eager-loading element ID mappings, or false if no mappings exist
	 */
	public function getEagerLoadingMap($sourceElements);
}
