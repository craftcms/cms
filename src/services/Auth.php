<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Base64Url\Base64Url;
use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\auth\methods\RecoveryCodes;
use craft\auth\methods\TOTP;
use craft\auth\passkeys\CredentialRepository;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\models\UserGroup;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\web\Session;
use DateTime;
use GuzzleHttp\Psr7\ServerRequest;
use Throwable;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * User authentication service.
 *
 * An instance of the service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAuth()|`Craft::$app->auth`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Auth extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering user authentication methods.
     * @see getAllMethods()
     */
    public const EVENT_REGISTER_METHODS = 'registerMethods';

    /**
     * @var string The session variable name used to store the ID of the user being authenticated.
     */
    public string $userIdParam;

    /**
     * @var string The session variable name used to store the number of seconds that the user can remain logged-in.
     */
    public string $sessionDurationParam;

    /**
     * @var string The session variable name used to store passkey credential creation options.
     */
    public string $passkeyCreationOptionsParam;

    /**
     * @var AuthMethodInterface[][] All user authentication methods
     * @see getAllMethods()
     */
    private array $_methods = [];

    /**
     * @var User|false The user being authenticated.
     * @see getUser()
     * @see setUser()
     */
    private User|false $_user;

    /**
     * @var Server
     * @see webauthnServer()
     */
    private Server $_webauthnServer;

    /**
     * @var int|false The session duration for the user being authenticated.
     * @see getUser()
     * @see setUser()
     */
    private int|false $_sessionDuration;

    public function init(): void
    {
        parent::init();

        $stateKeyPrefix = md5(sprintf('Craft.%s.%s', Session::class, Craft::$app->id));
        if (!isset($this->userIdParam)) {
            $this->userIdParam = sprintf('%s__userId', $stateKeyPrefix);
        }
        if (!isset($this->sessionDurationParam)) {
            $this->sessionDurationParam = sprintf('%s__duration', $stateKeyPrefix);
        }
        if (!isset($this->passkeyCreationOptionsParam)) {
            $this->passkeyCreationOptionsParam = sprintf('%s__pkCredCreationOptions', $stateKeyPrefix);
        }
    }

    /**
     * Get user and duration data from session
     *
     * @return User|null
     */
    public function getUser(?int &$sessionDuration = null): ?User
    {
        if (!isset($this->_user)) {
            $this->_user = false;
            $this->_sessionDuration = false;
            $session = Craft::$app->getSession();
            $userId = $session->get($this->userIdParam);

            if ($userId) {
                $user = User::findOne($userId);
                if ($user) {
                    $this->_user = $user;
                    $this->_sessionDuration = $session->get($this->sessionDurationParam) ?? false;
                }
            }
        }

        $sessionDuration = $this->_sessionDuration ?: null;
        return $this->_user ?: null;
    }

    /**
     * Stores the user being logged-in, along with the expected session duration.
     *
     * @param User|null $user
     * @param int|null $sessionDuration
     */
    public function setUser(?User $user, ?int $sessionDuration = null): void
    {
        $this->_user = $user ?? false;
        $this->_sessionDuration = $user ? ($sessionDuration ?? Craft::$app->getConfig()->getGeneral()->userSessionDuration) : false;
        $session = Craft::$app->getSession();

        if ($user) {
            $session->set($this->userIdParam, $user->id);
            $session->set($this->sessionDurationParam, $this->_sessionDuration);
        } else {
            $session->remove($this->userIdParam);
            $session->remove($this->sessionDurationParam);
        }
    }

    /**
     * Get html of the form for the 2FA step
     *
     * @return string
     */
    public function getInputHtml(): string
    {
        $user = $this->getUser();

        if (!$user) {
            return '';
        }

        $method = $this->getAvailableMethods()[0] ?? null;
        return $method?->getAuthFormHtml();
    }

    /**
     * Authenticates the user.
     *
     * Any arguments
     *
     * @param class-string<AuthMethodInterface> $methodClass
     * @param mixed $args,...
     * @return bool
     */
    public function verify(string $methodClass, mixed ...$args): bool
    {
        $user = $this->getUser($sessionDuration);

        if (!$this->getMethod($methodClass, $user)->verify(...$args)) {
            return false;
        }

        // success!
        if ($user) {
            $this->setUser(null);
            Craft::$app->getUser()->login($user, $sessionDuration);
        }

        return true;
    }

    /**
     * Returns all available user authentication methods.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     */
    public function getAllMethods(?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity() ?? $this->getUser();

        if (!$user?->id) {
            return [];
        }

        if (!isset($this->_methods[$user->id])) {
            $methods = [
                TOTP::class,
                RecoveryCodes::class,
            ];

            $event = new RegisterComponentTypesEvent([
                'types' => $methods,
            ]);
            $this->trigger(self::EVENT_REGISTER_METHODS, $event);

            $this->_methods[$user->id] = array_map(fn(string $class) => ComponentHelper::createComponent([
                'type' => $class,
                'user' => $user,
            ], AuthMethodInterface::class), $event->types);

            usort($this->_methods[$user->id], function(AuthMethodInterface $a, AuthMethodInterface $b) {
                // place Recovery Codes at the end
                if ($a instanceof RecoveryCodes) {
                    return 1;
                }
                if ($b instanceof RecoveryCodes) {
                    return -1;
                }

                return $a::displayName() <=> $b::displayName();
            });
        }

        return $this->_methods[$user->id];
    }

    /**
     * Returns the authentication methods that are available for the given user.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     */
    public function getAvailableMethods(?User $user = null): array
    {
        $methods = $this->getAllMethods($user);

        // only include Recovery Codes if at least one other method is active
        $hasActiveMethod = ArrayHelper::contains(
            $methods,
            fn(AuthMethodInterface $method) => !$method instanceof RecoveryCodes && $method->isActive(),
        );

        if ($hasActiveMethod) {
            return $methods;
        }

        return array_values(array_filter($methods, fn($method) => !$method instanceof RecoveryCodes));
    }

    /**
     * Returns whether any authentication methods are active for the given user.
     *
     * @param User|null $user
     * @return bool
     */
    public function hasActiveMethod(?User $user = null): bool
    {
        foreach ($this->getAvailableMethods($user) as $method) {
            if ($method->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the authentication methods that are active for the given user.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     */
    public function getActiveMethods(?User $user = null): array
    {
        return array_values(array_filter(
            $this->getAvailableMethods($user),
            fn(AuthMethodInterface $method) => $method->isActive(),
        ));
    }

    /**
     * Returns an authentication method by its class name.
     *
     * @template T of AuthMethodInterface
     * @param class-string<T> $class
     * @param User|null $user
     * @return T
     * @throws InvalidArgumentException
     */
    public function getMethod(string $class, ?User $user = null): AuthMethodInterface
    {
        foreach ($this->getAllMethods($user) as $method) {
            if (get_class($method) === $class) {
                return $method;
            }
        }

        throw new InvalidArgumentException("Invalid authentication method: $class");
    }

    /**
     * Returns whether 2FA is required for a user.
     *
     * @param User $user
     * @return bool
     */
    public function is2faRequired(User $user): bool
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return false;
        }

        $require2fa = Craft::$app->getProjectConfig()->get(sprintf('%s.require2fa', ProjectConfig::PATH_USERS));

        if ($require2fa === 'all') {
            return true;
        }

        if (is_array($require2fa)) {
            $groups = array_flip(array_map(fn(UserGroup $group) => $group->uid, $user->getGroups()));
            foreach ($require2fa as $group) {
                if ($group === 'admins') {
                    if ($user->admin) {
                        return true;
                    }
                } elseif (isset($groups[$group])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns whether the given user has passkeys.
     *
     * @param User $user
     * @return bool
     */
    public function hasPasskeys(User $user): bool
    {
        if (!$user->id) {
            return false;
        }

        return WebAuthnRecord::find()
            ->where(['userId' => $user->id])
            ->exists();
    }

    /**
     * Returns info about the given user’s saved passkeys.
     *
     * @param User $user
     * @return array{credentialName:string, dateLasteUsed:DateTime, uid:string}[]
     */
    public function getPasskeys(User $user): array
    {
        if (!$user->id) {
            return [];
        }

        /** @var array[] $passkeys */
        $passkeys = WebAuthnRecord::find()
            ->select(['credentialName', 'dateLastUsed', 'uid'])
            ->where(['userId' => $user->id])
            ->asArray()
            ->all();

        return array_map(function(array $passkey) {
            if ($passkey['dateLastUsed']) {
                $passkey['dateLastUsed'] = DateTimeHelper::toDateTime($passkey['dateLastUsed']);
            }
            return $passkey;
        }, $passkeys);
    }

    /**
     * Generates new passkey credential creation options for the given user.
     *
     * @param User $user
     * @return PublicKeyCredentialOptions
     */
    public function getPasskeyCreationOptions(User $user): PublicKeyCredentialOptions
    {
        $userEntity = $this->passkeyUserEntity($user);

        $excludeCredentials = array_map(
            fn(PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            (new CredentialRepository())->findAllForUserEntity($userEntity),
        );

        $options = $this->webauthnServer()->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            $this->passkeyAuthenticatorSelectionCriteria(),
        );

        Craft::$app->getSession()->set($this->passkeyCreationOptionsParam, Json::encode($options));
        return $options;
    }

    /**
     * Verifies a passkey creation response and stores the passkey.
     *
     * @param string $credentials
     * @param string|null $credentialName
     * @return bool
     */
    public function verifyPasskeyCreationResponse(string $credentials, ?string $credentialName = null): bool
    {
        $optionsJson = Craft::$app->getSession()->get($this->passkeyCreationOptionsParam);

        if (!$optionsJson) {
            return false;
        }

        /** @var PublicKeyCredentialCreationOptions $options */
        $options = PublicKeyCredentialCreationOptions::createFromArray(Json::decode($optionsJson));

        try {
            $verifiedCredentials = $this->webauthnServer()->loadAndCheckAttestationResponse(
                $credentials,
                $options,
                $this->webauthnServerRequest(),
            );
        } catch (Throwable) {
            return false;
        }

        $credentialRepository = new CredentialRepository();
        $credentialRepository->savedNamedCredentialSource($verifiedCredentials, $credentialName);

        return true;
    }

    /**
     * Verifies a passkey authentication response and stores the passkey.
     *
     * @param User $user
     * @param PublicKeyCredentialRequestOptions|array|string $requestOptions The public key credential request options
     * @param string $response The authentication response data
     * @return bool
     */
    public function verifyPasskey(
        User $user,
        PublicKeyCredentialRequestOptions|array|string $requestOptions,
        string $response,
    ): bool {
        if (is_array($requestOptions)) {
            $requestOptions = PublicKeyCredentialRequestOptions::createFromArray($requestOptions);
        } elseif (is_string($requestOptions)) {
            $requestOptions = PublicKeyCredentialRequestOptions::createFromString($requestOptions);
        }

        try {
            /** @var PublicKeyCredentialRequestOptions $requestOptions */
            $this->webauthnServer()->loadAndCheckAssertionResponse(
                $response,
                $requestOptions,
                $this->passkeyUserEntity($user),
                $this->webauthnServerRequest(),
            );
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a passkey.
     *
     * @param User $user
     * @param string $uid
     */
    public function deletePasskey(User $user, string $uid): void
    {
        WebAuthnRecord::findOne(['userId' => $user->id, 'uid' => $uid])?->delete();
    }

    /**
     * Returns the credential request options.
     *
     * @return PublicKeyCredentialRequestOptions
     */
    public function getPasskeyRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return $this->webauthnServer()->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
        );
    }

    private function webauthnServer(): Server
    {
        if (!isset($this->_webauthnServer)) {
            $this->_webauthnServer = new Server($this->passkeyRpEntity(), new CredentialRepository());
        }

        return $this->_webauthnServer;
    }

    private function webauthnServerRequest(): ServerRequest
    {
        $request = Craft::$app->getRequest();

        return new ServerRequest(
            $request->getMethod(),
            $request->getFullUri(),
            $request->getHeaders()->toArray(),
            $request->getRawBody(),
        );
    }

    private function passkeyUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        $data = [
            'name' => $user->email,
            'id' => Base64Url::encode($user->uid),
            'displayName' => $user->getName(),
        ];

        return PublicKeyCredentialUserEntity::createFromArray($data);
    }

    public function passkeyRpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::createFromArray([
            'name' => Craft::$app->getSystemName(),
            'id' => Craft::$app->getRequest()->getHostName(),
        ]);
    }

    private function passkeyAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();
        $authenticatorSelectionCriteria->setRequireResidentKey(true);
        $authenticatorSelectionCriteria->setUserVerification(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED);
        return $authenticatorSelectionCriteria;
    }
}
