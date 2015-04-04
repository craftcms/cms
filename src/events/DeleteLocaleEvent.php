<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Delete locale event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteLocaleEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var string The locale ID that is getting deleted.
	 */
	public $localeId;

	/**
	 * @var string|null The locale ID that the old locale's exclusive content should be transfered to.
	 */
	public $transferContentTo;
}
