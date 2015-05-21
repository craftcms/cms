<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\Query;
use craft\app\db\QueryAbortedException;
use craft\app\elements\User;
use craft\app\helpers\DbHelper;
use craft\app\models\UserGroup;

/**
 * UserQuery represents a SELECT SQL statement for users in a way that is independent of DBMS.
 *
 * @property string|string[]|UserGroup $group The handle(s) of the tag group(s) that resulting users must belong to.
 *
 * @method User[]|array all($db=null)
 * @method User|array|null one($db=null)
 * @method User|array|null nth($n,$db=null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserQuery extends ElementQuery
{
	// Properties
	// =========================================================================

	// General parameters
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public $orderBy = 'users.username';

	/**
	 * @inheritdoc
	 */
	public $status = User::STATUS_ACTIVE;

	/**
	 * @var boolean Whether to only return users that are admins.
	 */
	public $admin;

	/**
	 * @var boolean Whether to only return the client user.
	 */
	public $client;

	/**
	 * @var string|integer The permission that the resulting users must have.
	 */
	public $can;

	/**
	 * @var integer|integer[] The tag group ID(s) that the resulting users must be in.
	 */
	public $groupId;

	/**
	 * @var string|string[] The email address that the resulting users must have.
	 */
	public $email;

	/**
	 * @var string|string[] The username that the resulting users must have.
	 */
	public $username;

	/**
	 * @var string|string[] The first name that the resulting users must have.
	 */
	public $firstName;

	/**
	 * @var string|string[] The last name that the resulting users must have.
	 */
	public $lastName;

	/**
	 * @var mixed The date that the resulting entries must have last logged in.
	 */
	public $lastLoginDate;

	/**
	 * @var boolean Whether the users' passwords should be fetched.
	 */
	public $withPassword = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'group':
			{
				$this->group($value);
				break;
			}
			default:
			{
				parent::__set($name, $value);
			}
		}
	}

	/**
	 * Sets the [[admin]] property.
	 *
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function admin($value = true)
	{
		$this->admin = $value;
		return $this;
	}

	/**
	 * Sets the [[client]] property.
	 *
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function client($value = true)
	{
		$this->client = $value;
		return $this;
	}

	/**
	 * Sets the [[can]] property.
	 *
	 * @param string|integer $value The property value
	 * @return static The query object itself
	 */
	public function can($value)
	{
		$this->client = $value;
		return $this;
	}

	/**
	 * Sets the [[groupId]] property based on a given tag group(s)’s handle(s).
	 *
	 * @param string|string[]|UserGroup $value The property value
	 * @return static The query object itself
	 */
	public function group($value)
	{
		if ($value instanceof UserGroup)
		{
			$this->groupId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->groupId = $query
				->select('id')
				->from('{{%usergroups}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[groupId]] property.
	 *
	 * @param integer|integer[] $value The property value
	 *
	 * @return static The query object itself
	 */
	public function groupId($value)
	{
		$this->groupId = $value;
		return $this;
	}

	/**
	 * Sets the [[email]] property.
	 *
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function email($value)
	{
		$this->email = $value;
		return $this;
	}

	/**
	 * Sets the [[username]] property.
	 *
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function username($value)
	{
		$this->username = $value;
		return $this;
	}

	/**
	 * Sets the [[firstName]] property.
	 *
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function firstName($value)
	{
		$this->firstName = $value;
		return $this;
	}

	/**
	 * Sets the [[lastName]] property.
	 *
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function lastName($value)
	{
		$this->lastName = $value;
		return $this;
	}

	/**
	 * Sets the [[lastLoginDate]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function lastLoginDate($value)
	{
		$this->lastLoginDate = $value;
		return $this;
	}

	/**
	 * Sets the [[withPassword]] property.
	 *
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function withPassword($value = true)
	{
		$this->withPassword = $value;
		return $this;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare()
	{
		// See if 'group' was set to an invalid handle
		if ($this->groupId === [])
		{
			return false;
		}

		$this->joinElementTable('users');

		$this->query->select([
			'users.username',
			'users.photo',
			'users.firstName',
			'users.lastName',
			'users.email',
			'users.admin',
			'users.client',
			'users.locked',
			'users.pending',
			'users.suspended',
			'users.archived',
			'users.lastLoginDate',
			'users.lockoutDate',
		]);

		if ($this->withPassword)
		{
			$this->query->addSelect('users.password');
		}

		if ($this->admin)
		{
			$this->subQuery->andWhere('users.admin = 1');
		}
		else if ($this->client)
		{
			$this->subQuery->andWhere('users.client = 1');
		}
		else
		{
			$this->_applyCanParam();
		}

		if ($this->groupId)
		{
			$query = new Query();
			$userIds = $query
				->select('userId')
				->from('{{%usergroups_users}}')
				->where(DbHelper::parseParam('groupId', $this->groupId, $query->params))
				->column();

			if (!empty($userIds))
			{
				$this->subQuery->andWhere(['in', 'elements.id', $userIds]);
			}
			else
			{
				return false;
			}
		}

		if ($this->email)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('users.email', $this->email, $this->subQuery->params));
		}

		if ($this->username)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('users.username', $this->username, $this->subQuery->params));
		}

		if ($this->firstName)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('users.firstName', $this->firstName, $this->subQuery->params));
		}

		if ($this->lastName)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('users.lastName', $this->lastName, $this->subQuery->params));
		}

		if ($this->lastLoginDate)
		{
			$this->subQuery->andWhere(DbHelper::parseDateParam('entries.lastLoginDate', $this->lastLoginDate, $this->subQuery->params));
		}

		return parent::beforePrepare();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Applies the 'can' param to the query being prepared.
	 *
	 * @throws QueryAbortedException
	 */
	private function _applyCanParam()
	{
		if ($this->can === false || !empty($this->can))
		{
			if (is_string($this->can) && !is_numeric($this->can))
			{
				// Convert it to the actual permission ID, or false if the permission doesn't have an ID yet.
				$this->can = (new Query())
					->select('id')
					->from('{{%userpermissions}}')
					->where('name = :name', [':name' => strtolower($this->can)])
					->scalar();
			}

			// False means that the permission doesn't have an ID yet.
			if ($this->can !== false)
			{
				// Get the users that have that permission directly
				$permittedUserIds = (new Query())
					->select('userId')
					->from('{{%userpermissions_users}}')
					->where(['permissionId' => $this->can])
					->column();

				// Get the users that have that permission via a user group
				$permittedUserIdsViaGroups = (new Query())
					->select('ug_u.userId')
					->from('{{%usergroups_users}} g_u')
					->innerJoin('{{%userpermissions_usergroups}} p_g', 'p_g.groupId = g_u.groupId')
					->where(['p_g.permissionId' => $this->can])
					->column();

				$permittedUserIds = array_unique(array_merge($permittedUserIds, $permittedUserIdsViaGroups));
			}

			if (!empty($permittedUserIds))
			{
				$permissionConditions = ['or', 'users.admin = 1', ['in', 'elements.id', $permittedUserIds]];
			}
			else
			{
				$permissionConditions = 'users.admin = 1';
			}

			$this->subQuery->andWhere($permissionConditions);
		}
	}
}
