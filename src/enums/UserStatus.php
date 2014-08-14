<?php
namespace Craft;

/**
 * The UserStatus class is an abstract class that defines the different user account statuses available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class UserStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const Active    = 'active';
	const Locked    = 'locked';
	const Suspended = 'suspended';
	const Pending   = 'pending';
	const Archived  = 'archived';
}
