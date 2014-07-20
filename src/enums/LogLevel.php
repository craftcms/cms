<?php
namespace Craft;

/**
 * Just a duplicate of the consts in \CLogger for consistency!
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class LogLevel extends BaseEnum
{
	const Trace   = 'trace';
	const Warning = 'warning';
	const Error   = 'error';
	const Info    = 'info';
	const Profile = 'profile';
}
