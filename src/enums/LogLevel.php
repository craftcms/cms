<?php
namespace Craft;

/**
 * Just a duplicate of the consts in \CLogger for consistency!
 */
abstract class LogLevel extends BaseEnum
{
	const Trace   = 'trace';
	const Warning = 'warning';
	const Error   = 'error';
	const Info    = 'info';
	const Profile = 'profile';
}
