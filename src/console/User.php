<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use craft\elements\User as UserElement;
use yii\base\Component;
use yii\base\InvalidValueException;
use yii\web\IdentityInterface;

/**
 * The User component provides APIs for managing the user authentication status.
 * An instance of the User component is globally accessible in Craft via [[\craft\console\Application::getUser()|`Craft::$app->user`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class User extends Component
{
    /**
     * @var UserElement|IdentityInterface|false
     */
    private $_identity = false;

    /**
     * Returns whether the current user is an admin.
     *
     * @return bool Whether the current user is an admin.
     */
    public function getIsAdmin(): bool
    {
        $user = $this->getIdentity();

        return ($user && $user->admin);
    }

    /**
     * Returns whether the current user has a given permission.
     *
     * @param string $permissionName The name of the permission.
     * @return bool Whether the current user has the permission.
     */
    public function checkPermission(string $permissionName): bool
    {
        $user = $this->getIdentity();

        return ($user && $user->can($permissionName));
    }

    /**
     * Returns the current identity object.
     *
     * @return UserElement|IdentityInterface|false|null
     */
    public function getIdentity()
    {
        return $this->_identity ?: null;
    }

    /**
     * Sets the user identity object.
     *
     * @param IdentityInterface|null $identity The identity object. If null, it
     * means the current user will be a guest without any associated identity.
     * @throws InvalidValueException If `$identity` object does not implement [[IdentityInterface]].
     */
    public function setIdentity(IdentityInterface $identity = null)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
        } else if ($identity === null) {
            $this->_identity = null;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
        }
    }

    /**
     * Returns whether the current user is a guest (not authenticated).
     *
     * @return bool Whether the current user is a guest.
     */
    public function getIsGuest(): bool
    {
        return $this->getIdentity() === null;
    }

    /**
     * Returns the current user’s ID, if they are logged in.
     *
     * @return int|null
     * @see getIdentity()
     */
    public function getId()
    {
        $identity = $this->getIdentity();

        return $identity !== null ? $identity->getId() : null;
    }
}
