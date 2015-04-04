<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console;

use yii\base\Component;
use yii\base\InvalidValueException;
use yii\web\IdentityInterface;

/**
 * The User service provides APIs for managing the user authentication status.
 *
 * An instance of the User service is globally accessible in Craft via [[Application::userSession `Craft::$app->getUser()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends Component
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	private $_identity = false;

	// Public Methods
	// =========================================================================

	/**
	 * Returns a value indicating whether the user is a guest (not authenticated).
	 *
	 * @return bool Whether the current user is a guest.
	 */
	public function getIsGuest()
	{
		return $this->getIdentity() === null;
	 }

	/**
	 * Returns a value that uniquely represents the user.
	 *
	 * @return string|int The unique identifier for the user. If null, it means the user is a guest.
	 */
	public function getId()
	 {
		$identity = $this->getIdentity();
		return $identity !== null ? $identity->getId() : null;
	 }

	// Authorization
	// -------------------------------------------------------------------------

	/**
	 * Returns whether the current user is an admin.
	 *
	 * @return bool Whether the current user is an admin.
	 */
	public function getIsAdmin()
	{
		$user = $this->getIdentity();
		return ($user && $user->admin);
	}

	/**
	 * Returns whether the current user has a given permission.
	 *
	 * @param string $permissionName The name of the permission.
	 *
	 * @return bool Whether the current user has the permission.
	 */
	public function checkPermission($permissionName)
	{
		$user = $this->getIdentity();
		return ($user && $user->can($permissionName));
	}

	/**
	 * Returns the current identity object.
	 *
	 * @return bool|string
	 */
	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * Sets the user identity object.
	 *
	 * @param IdentityInterface|null $identity The identity object. If null, it means the current user will be
	 *                                         a guest without any associated identity.
	 *
	 * @throws InvalidValueException If `$identity` object does not implement [[IdentityInterface]].
	 */
	public function setIdentity($identity)
	{
		if ($identity instanceof IdentityInterface)
		{
			$this->_identity = $identity;
		}
		elseif ($identity === null)
		{
			$this->_identity = null;
		}
		else
		{
			throw new InvalidValueException('The identity object must implement IdentityInterface.');
		}
	}
}
