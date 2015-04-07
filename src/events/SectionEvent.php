<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Section event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SectionEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var \craft\app\models\Section The section model associated with the event.
	 */
	public $section;
}
