<?php
namespace Craft;

/**
 * Just a duplicate of the consts in \CLogger for consistency!
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class LogLevel extends BaseEnum
{
	const Trace   = 'trace';
	const Warning = 'warning';
	const Error   = 'error';
	const Info    = 'info';
	const Profile = 'profile';
}
