<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\Category;

/**
 * Category event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var Category The category model associated with the event.
	 */
	public $category;
}
