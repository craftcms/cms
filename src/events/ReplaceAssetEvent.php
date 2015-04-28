<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Replace asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ReplaceAssetEvent extends AssetEvent
{
	// Properties
	// =========================================================================

	/**
	 * @var string file on server that is being used to replace
	 */
	public $replaceWith;

	/**
	 * @var string the file name that will be used
	 */
	public $filename;

}
