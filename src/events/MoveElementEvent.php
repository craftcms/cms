<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\base\ElementInterface;

/**
 * Move element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MoveElementEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var int The ID of the structure the element is being moved within.
	 */
	public $structureId;

	/**
	 * @var ElementInterface The element being moved.
	 */
	public $element;
}
