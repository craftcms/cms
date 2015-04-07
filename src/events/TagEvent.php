<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\Tag;

/**
 * Tag event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var Tag The tag model associated with the event.
	 */
	public $tag;
}
