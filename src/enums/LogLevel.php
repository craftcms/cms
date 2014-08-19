<?php
namespace Craft;

/**
 * The LogLevel class is an abstract class that defines all of the different log level options that are available in
 * Craft when calling {@link Craft::log()}.
 *
 * These are just a duplicate of the constants in Yii's {@link \CLogger} for consistency!
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
