<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\auth\sso\ProviderInterface;
use craft\base\MemoizableArray;
use craft\db\Table;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\errors\AuthProviderNotFoundException;
use craft\errors\SsoFailedException;
use craft\helpers\User as UserHelper;
use craft\records\SsoIdentity;
use craft\records\SsoIdentity as AuthRecord;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * SSO service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getSso()|`Craft::$app->getSso()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
class Sso extends Component
{
    /**
     * @event UserEvent The event that is triggered when populating user groups from an SSO provider.
     *
     * ---
     * ```php
     * use craft\events\UserGroupsAssignEvent;
     * use craft\services\Sso;
     * use yii\base\Event;
     *
     * Event::on(
     *     \some\provider\Type::class,
     *     Sso::EVENT_POPULATE_USER_GROUPS,
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
     * @event UserEvent The event that is triggered when populating a user from an SSO provider.
     *
     * ---
     * ```php
     * use craft\events\UserEvent;
     * use craft\services\Sso;
     * use yii\base\Event;
     *
     * Event::on(
     *     \some\provider\Type::class,
     *     Sso::EVENT_POPULATE_USER,
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
     * Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        Craft::$app->requireEdition(CmsEdition::Enterprise);
        parent::__construct($config);
    }

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
     * @param array $configs
     * @return MemoizableArray<ProviderInterface>
     * @throws InvalidConfigException
     */
    private function initProviders(array $configs = []): MemoizableArray
    {
        $providers = array_map(function(string $handle, array $config) {
            $config['handle'] = $handle;
            return $this->createAuthProvider($config);
        }, array_keys($configs), $configs);

        return new MemoizableArray($providers);
    }

    /**
     * Creates an auth provider from a given config.
     *
     * @param mixed $config
     * @return ProviderInterface
     * @throws InvalidConfigException
     */
    public function createAuthProvider(mixed $config): ProviderInterface
    {
        if ($config instanceof ProviderInterface) {
            return $config;
        }

        if (is_string($config)) {
            $config = ['class' => $config];
        }

        $provider = Craft::createObject($config);

        // Verify the created object instance
        if ($provider instanceof ProviderInterface) {
            return $provider;
        }

        throw new InvalidConfigException('Provider must implement "ProviderInterface"');
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
     * Find a user based on the identity provider and identity id
     *
     * @param ProviderInterface $provider
     * @param string $idpIdentifier
     * @return User|null
     */
    public function findUser(ProviderInterface $provider, string $idpIdentifier): ?User
    {
        return User::find()
            ->innerJoin(
                ['s_i' => Table::SSO_IDENTITIES],
                '[[s_i.userId]] = [[users.id]]',
            )
            ->andWhere(
                [
                    's_i.provider' => $provider->getHandle(),
                    's_i.identityId' => $idpIdentifier,
                ]
            )
            ->one();
    }

    /**
     * Assigns a user to an identity
     *
     * @param User $user
     * @param ProviderInterface $provider
     * @param string $idpIdentifier
     * @return bool
     */
    public function linkUserToIdentity(User $user, ProviderInterface $provider, string $idpIdentifier): bool
    {
        $authRecord = AuthRecord::find()
            ->where([
                'provider' => $provider->getHandle(),
                'identityId' => $idpIdentifier,
                'userId' => $user->getId(),
            ])
            ->one();

        if (!$authRecord) {
            $authRecord = new AuthRecord([
                'provider' => $provider->getHandle(),
                'identityId' => $idpIdentifier,
                'userId' => $user->getId(),
            ]);
        }

        return $authRecord->save();
    }

    /**
     * @param ProviderInterface $provider
     * @param User $user
     * @param int|null $sessionDuration
     * @param bool $rememberMe
     * @return bool
     * @throws SsoFailedException
     */
    public function loginUser(ProviderInterface $provider, User $user, ?int $sessionDuration = null, bool $rememberMe = false): bool
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
            throw new SsoFailedException($provider, $user, $user->authError);
        }

        // Try logging them in
        if (!$userSession->login($user, $sessionDuration)) {
            throw new SsoFailedException($provider, $user, Craft::t('auth', "Unable to login"));
        }

        return true;
    }

    /**
     * Returns whether the given user ID has an associated SSO identity.
     *
     * @param int $userId
     * @return bool
     * @since 5.7.8
     */
    public function identityExists(int $userId): bool
    {
        return SsoIdentity::find()
            ->where(['userId' => $userId])
            ->exists();
    }
}
