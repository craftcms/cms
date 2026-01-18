<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\auth\methods\RecoveryCodes;
use craft\auth\methods\TOTP;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Session as SessionHelper;
use craft\helpers\User as UserHelper;
use craft\web\Session;
use craft\web\View;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\IdentityWrapper;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use function CraftCms\Cms\t;

/**
 * User authentication service.
 *
 * An instance of the service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAuth()|`Craft::$app->getAuth()`]].
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
     * @param int|null $sessionDuration
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
        $this->_sessionDuration = $user ? ($sessionDuration ?? Cms::config()->userSessionDuration) : false;

        if ($user) {
            SessionHelper::set($this->userIdParam, $user->id);
            SessionHelper::set($this->sessionDurationParam, $this->_sessionDuration);
        } else {
            SessionHelper::remove($this->userIdParam);
            SessionHelper::remove($this->sessionDurationParam);
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
        if (!$method) {
            return '';
        }

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        try {
            return $method->getAuthFormHtml();
        } finally {
            $view->setTemplateMode($templateMode);
        }
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
            $user?->handleInvalidLoginParam();
            return false;
        }

        // success!
        if ($user) {
            $this->setUser(null);

            // if we're impersonating, pass the user we're impersonating to the complete the login
            $userSession = Craft::$app->getUser();
            if ($userSession->getImpersonator() !== null) {
                /** @var User $user */
                $user = AuthFacade::user();
            }

            $userSession->login(new IdentityWrapper($user), $sessionDuration);
        }

        return true;
    }

    /**
     * Returns an authentication error message based on the authentication error value.
     * If a default message was passed and the authentication error value is for invalid credentials,
     * that default message will be used.
     *
     * @param string|null $defaultMessage
     * @return string
     * @since 5.7.11
     */
    public function getAuthErrorMessage(?string $defaultMessage = null): string
    {
        $user = $this->getUser();
        $authError = null;
        if ($user) {
            $authError = UserHelper::getAuthStatus($user);
        }
        if ($authError == User::AUTH_INVALID_CREDENTIALS || !$authError) {
            if ($defaultMessage) {
                return $defaultMessage;
            }

            return t('Invalid verification code.');
        }

        [, $message] = UserHelper::getLoginFailureInfo($authError, $user);
        return $message;
    }

    /**
     * Returns all available user authentication methods.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     */
    public function getAllMethods(?User $user = null): array
    {
        $user ??= AuthFacade::user() ?? $this->getUser();

        if (!$user?->id) {
            return [];
        }

        if (!isset($this->_methods[$user->id])) {
            $methods = [
                TOTP::class,
                RecoveryCodes::class,
            ];

            // Fire a 'registerMethods' event
            if ($this->hasEventHandlers(self::EVENT_REGISTER_METHODS)) {
                $event = new RegisterComponentTypesEvent(['types' => $methods]);
                $this->trigger(self::EVENT_REGISTER_METHODS, $event);
                $methods = $event->types;
            }

            $this->_methods[$user->id] = array_map(fn(string $class) => ComponentHelper::createComponent([
                'type' => $class,
                'user' => $user,
            ], AuthMethodInterface::class), $methods);

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
        $hasActiveMethod = Collection::make($methods)->contains(
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
        if (Edition::get() === Edition::Solo) {
            return false;
        }

        $require2fa = app(ProjectConfig::class)->get(sprintf('%s.require2fa', ProjectConfig::PATH_USERS));

        if ($require2fa === 'all') {
            return true;
        }

        if (is_array($require2fa)) {
            $groups = Arr::pluck(array: $user->getGroups(), value: '', key: 'uid');
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
     * @deprecated 6.0.0 use {@see Passkeys::hasPasskeys} instead.
     */
    public function hasPasskeys(User $user): bool
    {
        return app(Passkeys::class)->hasPasskeys($user);
    }

    /**
     * Returns info about the given user’s saved passkeys.
     *
     * @param User $user
     *
     * @return array{credentialName:string, dateLastUsed:DateTime|null, uid:string}[]
     * @deprecated 6.0.0 use {@see Passkeys::getPasskeys} instead.
     */
    public function getPasskeys(User $user): array
    {
        return app(Passkeys::class)->getPasskeys($user)->all();
    }

    /**
     * Generates new passkey credential creation options for the given user.
     *
     * @param User $user
     *
     * @return PublicKeyCredentialOptions
     * @deprecated 6.0.0 use {@see Passkeys::getPasskeyCreationOptions} instead.
     */
    public function getPasskeyCreationOptions(User $user): PublicKeyCredentialOptions
    {
        return app(Passkeys::class)->getPasskeyCreationOptions($user);
    }

    /**
     * Verifies a passkey creation response and stores the passkey.
     *
     * @param string $credentials
     * @param string|null $credentialName
     *
     * @return bool
     * @deprecated 6.0.0 use {@see Passkeys::verifyPasskeyCreationResponse} instead.
     */
    public function verifyPasskeyCreationResponse(string $credentials, ?string $credentialName = null): bool
    {
        return app(Passkeys::class)->verifyPasskeyCreationResponse($credentials, $credentialName);
    }

    /**
     * Returns the public key credential request options.
     *
     * @return PublicKeyCredentialRequestOptions
     * @deprecated 6.0.0 use {@see Passkeys::getPasskeyRequestOptions} instead.
     */
    public function getPasskeyRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return app(Passkeys::class)->getPasskeyRequestOptions();
    }

    /**
     * Verifies a passkey authentication response and stores the passkey.
     *
     * @param User $user
     * @param PublicKeyCredentialRequestOptions|array|string $requestOptions The public key credential request options
     * @param string $response The authentication response data
     * @return bool
     * @deprecated 6.0.0 use {@see Passkeys::verifyPasskey} instead.
     */
    public function verifyPasskey(
        User $user,
        PublicKeyCredentialRequestOptions|array|string $requestOptions,
        string $response,
    ): bool {
        return app(Passkeys::class)->verifyPasskey($user, $requestOptions, $response);
    }

    /**
     * Deletes a passkey.
     *
     * @param User $user
     * @param string $uid
     * @deprecated 6.0.0 use {@see Passkeys::deletePasskey} instead.
     */
    public function deletePasskey(User $user, string $uid): void
    {
        app(Passkeys::class)->deletePasskey($user, $uid);
    }
}
