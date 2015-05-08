<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * DB backup event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DbBackupEvent extends Event
{
	// Properties
	// =========================================================================

	/**
	 * @var string The file path to the backup
	 */
	public $filePath;
}
