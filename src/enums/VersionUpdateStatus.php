<?php
namespace Craft;

/**
 * Class VersionUpdateStatus
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class VersionUpdateStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const UpToDate        = 'UpToDate';
	const UpdateAvailable = 'UpdateAvailable';
}
