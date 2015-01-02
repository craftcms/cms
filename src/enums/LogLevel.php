<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The LogLevel class is an abstract class that defines all of the different log level options that are available in
 * Craft when calling [[Craft::log()]].
 *
 * These are just a duplicate of the constants in Yii's [[\CLogger]] for consistency!
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class LogLevel extends BaseEnum
{
	// Constants
	// =========================================================================

	const Trace   = 'trace';
	const Warning = 'warning';
	const Error   = 'error';
	const Info    = 'info';
	const Profile = 'profile';
}
