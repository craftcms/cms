<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use craft\elements\User as UserElement;
use yii\base\Component;

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
     * @var UserElement|null
     * @see getIdentity()
     * @see setIdentity()
     */
    private ?UserElement $_identity = null;

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
     * @param bool $autoRenew
     * @return UserElement|null
     */
    public function getIdentity(bool $autoRenew = true): UserElement|null
    {
        return $this->_identity;
    }

    /**
     * Sets the user identity object.
     *
     * @param UserElement|null $identity The identity object. If null, it
     * means the current user will be a guest without any associated identity.
     */
    public function setIdentity(?UserElement $identity = null): void
    {
        $this->_identity = $identity;
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
    public function getId(): ?int
    {
        $identity = $this->getIdentity();

        return $identity?->getId();
    }
}
