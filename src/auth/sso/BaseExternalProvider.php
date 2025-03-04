<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso;

use Craft;
use craft\auth\sso\mapper\UserAttributesMapper;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\errors\SsoFailedException;
use craft\events\SsoEvent;
use craft\events\UserGroupsAssignEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\services\Sso;

/**
 * BaseExternalProvider provides a base implementation for external identity providers.
 *
 * We should always trust an external provider; therefore we need to perform additional
 * operations such as finding, populating and linking a Craft user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
abstract class BaseExternalProvider extends BaseProvider
{
    /**
     * Override the default IdP unique identifier.  This value will be used to uniquely link a Craft User
     * to an IdP.
     *
     * The callable should return a string value
     *
     * @var callable|null
     *
     * ---
     * ```php
     * function(array $data, string $default) {
     *     return $data['sub'] ?? $default
     * }
     * ```
     */
    public $idpUniqueIdentifier = null;

    /**
     * The callable should return a Craft User.
     *
     * @var callable|null
     *
     * ---
     * ```php
     * function(array $data) {
     *     return Craft::$app->getUsers()->getUserByUsernameOrEmail($data['email']);
     * }
     * ```
     */
    public $findUser = null;

    /**
     * The callable should return a Craft User.
     *
     * @var callable|null
     *
     * ---
     * ```php
     * function(User $user, array $data) {
     *     $user->email = $data['email'];
     *
     *     return $user;
     * }
     * ```
     */
    public $populateUser = null;

    /**
     * @var callable|null
     *
     * ---
     * ```php
     * function(array $groupIds, array $data) {
     *     if (!$data['admin']) {
     *         unset($groupIds[2]);
     *     }
     *
     *     return $groupIds;
     * }
     * ```
     */
    public $assignUserGroups = null;

    /**
     * The URL that we will re-direct the user to for authentication
     *
     * @return string|null The auth request URL
     */
    protected function getRequestUrl(): ?string
    {
        return UrlHelper::actionUrl('sso/request', ['provider' => $this->handle], null, false);
    }

    /**
     * The URL that the IdP should send the authentication response to
     *
     * @return string|null The response URL
     */
    protected function getResponseUrl(): ?string
    {
        return UrlHelper::actionUrl('sso/response', ['provider' => $this->handle], null, false);
    }

    /**
     * @inheritdoc
     */
    public function getSiteLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: Craft::t('app', 'Sign in with {name}', [
            'name' => $this->name ?: static::displayName(),
        ]), $url ?: $this->getRequestUrl());
    }

    /**
     * @inheritdoc
     */
    public function getCpLoginHtml(?string $label = null, ?string $url = null): string
    {
        return Html::a($label ?: Craft::t('app', 'Sign in with {name}', [
            'name' => $this->name ?: static::displayName(),
        ]), $url ?: $this->getRequestUrl(), [
            'class' => 'btn',
        ]);
    }

    /**
     * @param array $data
     * @param string|null $idpIdentifier
     * @return User|null
     * @throws \yii\base\InvalidConfigException
     */
    protected function findUser(array $data, ?string $idpIdentifier = null): ?User
    {
        // First, look in storage
        if ($idpIdentifier) {
            $user = Craft::$app->getSso()->findUser(
                $this,
                (string) $idpIdentifier
            );

            if ($user) {
                return $user;
            }
        }

        // Second, check for a provider specific callable
        $findUser = $this->normalizeCallback($this->findUser);
        if (is_callable($findUser)) {
            return call_user_func_array($findUser, [$data]);
        }

        // Finally, attempt via username / email
        if ($idpIdentifier) {
            return Craft::$app->getUsers()->getUserByUsernameOrEmail(
                $idpIdentifier
            );
        }

        return null;
    }

    /**
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function syncUser(User $user, array $data, string $idpIdentifier): User
    {
        // Ensure the user is active
        if ($user->getStatus() !== User::STATUS_ACTIVE) {
            $this->enableUser($user);
        }

        // If the user has an ID, don't mess with username/em
        if (!$user->getId()) {
            if (!$user->username || Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
                $user->username = $user->email;
            }
        }

        // Save user
        if (!Craft::$app->getElements()->saveElement($user)) {
            throw new SsoFailedException(
                $this,
                $user,
                sprintf(
                    "Failed to save user: %s",
                    StringHelper::toString($user->getFirstErrors(), ', ')
                )
            );
        }

        // Link User to IdP for future logins
        Craft::$app->getSso()->linkUserToIdentity($user, $this, $idpIdentifier);

        // Assign User Groups
        $this->assignUserToGroups($user, $data);

        return $user;
    }

    /**
     * @param User $user
     * @param array $data
     * @return bool
     * @throws \Throwable
     */
    private function assignUserToGroups(User $user, array $data): bool
    {
        $db = Craft::$app->getDb();

        // Get the current groups
        $groupIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERGROUPS_USERS])
            ->where(['userId' => $user->getId()])
            ->column($db);

        // Populate user via callable
        $assignUserGroups = $this->normalizeCallback($this->assignUserGroups);
        if (is_callable($assignUserGroups)) {
            $groupIds = call_user_func_array($assignUserGroups, [$groupIds, $data]);
        }

        // TODO - New event that only has these two properties?
        $event = new UserGroupsAssignEvent([
            'userId' => $user->getId(),
            'groupIds' => $groupIds,
        ]);

        $this->trigger(Sso::EVENT_POPULATE_USER_GROUPS, $event);

        return Craft::$app->getUsers()->assignUserToGroups(
            $user->getId(),
            $event->groupIds,
        );
    }

    /**
     * @param User $user
     * @throws \Throwable
     */
    private function enableUser(User $user): void
    {
        if ($user->getId()) {
            Craft::$app->getUsers()->activateUser($user);

            return;
        }

        $user->enabled = true;
        $user->archived = false;

        $user->active = true;
        $user->pending = false;
        $user->locked = false;
        $user->suspended = false;
        $user->verificationCode = null;
        $user->verificationCodeIssuedDate = null;
        $user->invalidLoginCount = null;
        $user->lastInvalidLoginDate = null;
        $user->lockoutDate = null;
    }

    /**
     * Find or create, populate and sync a provider resource user to Craft User
     *
     * @param array $data
     * @param string|null $idpIdentifier
     * @return User
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function resolveUser(array $data, ?string $idpIdentifier = null): User
    {
        $user = $this->findUser($data, $idpIdentifier) ?? new User();

        return $this->syncUser(
            $this->populateUser(
                $user,
                $data,
            ),
            $data,
            $idpIdentifier
        );
    }

    /**
     * Populate a User
     *
     * @param User $user
     * @param array $data
     *
     * @return User
     */
    protected function populateUser(User $user, array $data): User
    {
        // Populate user via callable
        $populateUser = $this->normalizeCallback($this->populateUser, UserAttributesMapper::class);
        if (is_callable($populateUser)) {
            $user = call_user_func_array($populateUser, [$user, $data]);
        }

        $event = new SsoEvent([
            'user' => $user,
            'provider' => $this,
            'sender' => $data,
        ]);

        $this->trigger(Sso::EVENT_POPULATE_USER, $event);

        return $event->user;
    }

    /**
     * Normalize a callback
     *
     * @param mixed $callback
     * @param string|null $defaultClass
     * @return callable|null
     * @throws \yii\base\InvalidConfigException
     */
    protected function normalizeCallback(mixed $callback, string $defaultClass = null): ?callable
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_string($callback)) {
            $callback = [
                'class' => $callback,
            ];
        }

        if (is_array($callback)) {
            if ($defaultClass) {
                $callback = ArrayHelper::merge(
                    [
                        'class' => $defaultClass,
                    ],
                    $callback
                );
            }

            $callable = Craft::createObject($callback);

            if (is_callable($callable)) {
                return $callable;
            }
        }

        return null;
    }
}
