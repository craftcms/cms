<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

use craft\app\elements\Asset;

/**
 * Asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var Asset The asset model associated with the event.
	 */
	public $asset;
}
