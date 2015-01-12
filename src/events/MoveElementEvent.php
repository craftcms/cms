<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

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
	 * @var \craft\app\models\BaseElementModel The element being moved.
	 */
	public $element;
}
