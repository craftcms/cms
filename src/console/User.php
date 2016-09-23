<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\console;

use craft\app\elements\User as UserElement;
use yii\base\Component;
use yii\base\InvalidValueException;
use yii\web\IdentityInterface;

/**
 * The User service provides APIs for managing the user authentication status.
 *
 * An instance of the User service is globally accessible in Craft via [[Application::userSession `Craft::$app->getUser()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class User extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var UserElement|false
     */
    private $_identity = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     *
     * @return boolean Whether the current user is a guest.
     */
    public function getIsGuest()
    {
        return $this->getIdentity() === null;
    }

    // Authorization
    // -------------------------------------------------------------------------

    /**
     * Returns whether the current user is an admin.
     *
     * @return boolean Whether the current user is an admin.
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
     * @return boolean Whether the current user has the permission.
     */
    public function checkPermission($permissionName)
    {
        $user = $this->getIdentity();

        return ($user && $user->can($permissionName));
    }

    /**
     * Returns the current identity object.
     *
     * @return UserElement|false
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
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
        } else if ($identity === null) {
            $this->_identity = null;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
        }
    }
}
