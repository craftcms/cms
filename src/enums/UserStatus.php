<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The UserStatus class is an abstract class that defines the different user account statuses available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
