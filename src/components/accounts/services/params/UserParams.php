<?php
namespace Blocks;

/**
 * User parameters
 */
class UserParams extends BaseParams
{
	public $id;
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
