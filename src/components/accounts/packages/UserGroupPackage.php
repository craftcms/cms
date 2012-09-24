<?php
namespace Blocks;

/**
 * User group package class
 *
 * Used for transporting user group data throughout the system.
 */
class UserGroupPackage extends BasePackage
{
	public $name;
	public $handle;

	/**
	 * Saves the entry block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->userGroups->saveGroup($this);
	}
}
