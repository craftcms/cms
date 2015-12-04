<?php
namespace Craft;

/**
 * The PluginUpdateStatus class is an abstract class that defines the different plugin version update status
 * states available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class PluginUpdateStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const UpToDate        = 'UpToDate';
	const UpdateAvailable = 'UpdateAvailable';
	const Deleted         = 'Deleted';
	const Unknown         = 'Unknown';
}
