<?php
namespace Craft;

/**
 * Class UserStatus
 *
 * @package craft.app.enums
 */
abstract class UserStatus extends BaseEnum
{
	const Active    = 'active';
	const Locked    = 'locked';
	const Suspended = 'suspended';
	const Pending   = 'pending';
	const Archived  = 'archived';
}
