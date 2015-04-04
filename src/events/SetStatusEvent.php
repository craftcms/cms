<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\db\ElementQueryInterface;

/**
 * Set Status element action event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SetStatusEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementQueryInterface The element query representing the elements that just got updated.
	 */
	public $elementQuery;

	/**
	 * @var array The element IDs that are getting updated.
	 */
	public $elementIds;

	/**
	 * @var string The status the elements are getting set to.
	 */
	public $status;
}
