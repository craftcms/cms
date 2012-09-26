<?php
namespace Blocks;

/**
 * User parameters
 */
class UserParams extends BaseParams
{
	public $id;
	/* BLOCKSPRO ONLY */
	public $groupId;
	public $group;
	/* end BLOCKSPRO ONLY */
	public $username;
	public $firstName;
	public $lastName;
	public $email;
	public $admin;
	public $status;
	public $lastLoginDate;
	public $order = 'username asc';
	public $offset;
	public $limit;
}
