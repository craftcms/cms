<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Content event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ContentEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\Content The content model associated with the event.
	 */
	public $content;
}
