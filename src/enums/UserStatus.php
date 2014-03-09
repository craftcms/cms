<?php
namespace Craft;

/**
 *
 */
abstract class UserStatus extends BaseEnum
{
	const Active                = 'active';
	const Locked                = 'locked';
	const Suspended             = 'suspended';
	const Pending               = 'pending';
	const Archived              = 'archived';
}
