<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Event extends \yii\base\Event
{
	// Properties
	// =========================================================================

	/**
	 * @var bool Whether the caller should go through with the action.
	 */
	public $performAction = true;
}
