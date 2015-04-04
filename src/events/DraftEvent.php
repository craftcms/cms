<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Draft event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DraftEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\EntryDraft The draft model associated with the event.
	 */
	public $draft;
}
