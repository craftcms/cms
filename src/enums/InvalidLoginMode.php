<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The InvalidLoginMode class is an abstract class that defines all of the invalid login modes that are available in
 * Craft when a user has unsuccessfully attempted to log into their account the number of times specified by the
 * [maxInvalidLogins](http://buildwithcraft.com/docs/config-settings#maxInvalidLogins) config setting.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class InvalidLoginMode extends BaseEnum
{
	// Constants
	// =========================================================================

	const Cooldown = 'cooldown';
	const Lockout  = 'lockout';
}
