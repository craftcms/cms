<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\errors\AuthFailedException;
use craft\errors\AuthProviderNotFoundException;
use craft\errors\MissingComponentException;
use craft\events\UserAuthEvent;
use craft\events\UserGroupsAssignEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\auth\ProviderInterface;
use craft\helpers\User as UserHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use Throwable;

class Auth extends Component
{
    const PROJECT_CONFIG_PATH = 'auth';

    /**
     * @event UserEvent The event that is triggered when populating user groups from an Auth provider.
     *
     * ---
     * ```php
     * use craft\events\UserGroupsAssignEvent;
     * use craft\services\Auth;
     * use yii\base\Event;
     *
     * Event::on(
     *     \some\provider\Type::class,
     *     Auth::EVENT_POPULATE_USER_GROUPS,
     *     function(UserGroupsAssignEvent $event) {
     *         $providerData = $event->sender;
     *
     *         // Assign to group 4?
     *         if ($providerData->some_attribute === 'some_value') {
     *             $event->groupIds[] = 4;
     *             $event->newGroupIds[] = 4;
     *         }
     *     }
     * );
     * ```
     */
    public const EVENT_POPULATE_USER_GROUPS = 'populateUserGroups';

    /**
     * @event UserEvent The event that is triggered when populating a user from an Auth provider.
     *
     * ---
     * ```php
     * use craft\events\UserEvent;
     * use craft\services\Auth;
     * use yii\base\Event;
     *
     * Event::on(
     *     \some\provider\Type::class,
     *     Auth::EVENT_POPULATE_USER,
     *     function(UserEvent $event) {
     *         $providerData = $event->sender;
     *
     *         $event->user->firstName = $providerData->some_attribute;
     *     }
     * );
     * ```
     */
    public const EVENT_POPULATE_USER = 'populateUser';

    /**
     * @var MemoizableArray<ProviderInterface>|null
     * @see _providers()
     */
    private ?MemoizableArray $_providers = null;

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_providers']);
        return $vars;
    }

    /**
     * @param ProviderInterface[] $providers list of Authorization Providers
     */
    protected function setProviders(array $providers)
    {
        $this->_providers = $this->initProviders($providers);
    }

    /**
     * Returns a memoizable array of all providers.
     *
     * @return MemoizableArray<ProviderInterface>
     */
    private function _providers(): MemoizableArray
    {
        if (!isset($this->_providers)) {
            $this->_providers = $this->initProviders();
        }

        return $this->_providers;
    }

    /**
     * @param array $baseProviders
     * @return MemoizableArray<ProviderInterface>
     */
    private function initProviders(array $baseProviders = []): MemoizableArray
    {
        $configs = ArrayHelper::merge(
            $baseProviders,
            Craft::$app->getProjectConfig()->get(self::PROJECT_CONFIG_PATH) ?? []
        );

        $providers = array_map(function(string $handle, array $config) {
            $config['handle'] = $handle;
            $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);
            return $this->createAuthProvider($config);
        }, array_keys($configs), $configs);

        return new MemoizableArray($providers);
    }

    /**
     * Creates an auth provider from a given config.
     *
     * @template T as ProviderInterface
     * @param string|array $config The auth provider’s class name, or its config, with a `type` value and optionally a `settings` value
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The filesystem
     */
    public function createAuthProvider(mixed $config): ProviderInterface
    {
        try {
            return ComponentHelper::createComponent($config, ProviderInterface::class);
        } catch (MissingComponentException|InvalidConfigException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'] ?? 'ProviderInterface';
            /** @var array $config */
            /** @phpstan-var array{errorMessage:string,expectedType:string,type:string} $config */
            unset($config['type']);
            return new Exception("Invalid auth provider");
        }
    }

    /**
     * Removes a filesystem.
     *
     * @param ProviderInterface $provider The auth provider to remove
     * @return bool
     * @throws Throwable
     */
    public function removeAuthProvider(ProviderInterface $provider): bool
    {
        if (!$provider->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(sprintf('%s.%s', static::PROJECT_CONFIG_PATH, $provider->handle), "Remove the “{$provider->handle}” auth provider");

        // Clear caches
        $this->_providers = null;

        return true;
    }

    /**
     * Return a list of all auth providers
     *
     * @return ProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->_providers()->all();
    }

    /**
     * Returns an auth provider by its handle.
     *
     * @param string $handle
     * @return ProviderInterface|null
     */
    public function findProviderByHandle(string $handle): ?ProviderInterface
    {
        return $this->_providers()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns an auth provider by its handle.
     *
     * @param string $handle
     * @return ProviderInterface
     * @throws AuthProviderNotFoundException
     */
    public function getProviderByHandle(string $handle): ProviderInterface
    {
        $provider = $this->findProviderByHandle($handle);
        if (!$provider) {
            throw new AuthProviderNotFoundException();
        }

        return $provider;
    }

    /**
     * @param ProviderInterface $provider
     * @param User $user
     * @param int|null $sessionDuration
     * @param bool $rememberMe
     * @return bool
     * @throws AuthFailedException
     */
    public function loginUser(ProviderInterface $provider, User $user, ?int $sessionDuration = null, bool $rememberMe = false, ): bool
    {
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            return true;
        }

        if (empty($sessionDuration)) {
            // Get the session duration
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
                $sessionDuration = $generalConfig->rememberedUserSessionDuration;
            } else {
                $sessionDuration = $generalConfig->userSessionDuration;
            }
        }

        $user->authError = UserHelper::getAuthStatus($user);

        if (!empty($user->authError)) {
            throw new AuthFailedException($provider, $user, $user->authError);
        }

        // Try logging them in
        if (!$userSession->login($user, $sessionDuration)) {
            throw new AuthFailedException($provider, $user, Craft::t('auth', "Unable to login"));
        }

        return true;
    }

    /**
     * Populate a User
     *
     * @param ProviderInterface $provider
     * @param mixed $data
     * @param User $user
     * @return User
     */
    public function populateUser(ProviderInterface $provider, mixed $data, User $user): User
    {
        $event = new UserAuthEvent([
            'user' => $user,
            'provider' => $provider,
            'sender' => $data
        ]);

        $provider->trigger(self::EVENT_POPULATE_USER, $event);

        return $event->user;
    }

    /**
     * @param ProviderInterface $provider
     * @param User $user
     * @param mixed $data
     * @return User
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function syncUser(ProviderInterface $provider, User $user, mixed $data): User
    {
        // Save user
        Craft::$app->getElements()->saveElement($user);

        // Assign User Groups
        $this->assignUserToGroups($provider, $user, $data);

        return $user;
    }

    /**
     * @param ProviderInterface $provider
     * @param User $user
     * @param mixed $data
     * @return bool
     * @throws \Throwable
     */
    private function assignUserToGroups(ProviderInterface $provider, User $user, mixed $data): bool
    {
        $db = Craft::$app->getDb();

        // Get the current groups
        $groupIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERGROUPS_USERS])
            ->where(['userId' => $user->getId()])
            ->column($db);

        // TODO - New event that only has these two properties?
        $event = new UserGroupsAssignEvent([
            'userId' => $user->getId(),
            'groupIds' => $groupIds,
            'data' => $data
        ]);

        $provider->trigger(self::EVENT_POPULATE_USER_GROUPS, $event);

        return Craft::$app->getUsers()->assignUserToGroups(
            $user->getId(),
            $event->groupIds
        );
    }
}
