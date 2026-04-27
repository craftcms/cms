<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Auth\Events\RegisterAuthMethods;
use CraftCms\Cms\Auth\Events\SettingPassword;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Validation\UserRules;
use CraftCms\Cms\View\TemplateMode;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use yii\base\Component;
use function CraftCms\Cms\t;

/**
 * User authentication service.
 *
 * An instance of the service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAuth()|`Craft::$app->getAuth()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth} instead.
 */
class Auth extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering user authentication methods.
     * @see getAllMethods()
     */
    public const EVENT_REGISTER_METHODS = 'registerMethods';

    /**
     * Get user and duration data from session
     *
     * @param int|null $sessionDuration
     * @return User|null
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getUser()} instead.
     */
    public function getUser(?int &$sessionDuration = null): ?User
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getUser();
    }

    /**
     * Stores the user being logged-in, along with the expected session duration.
     *
     * @param User|null $user
     * @param int|null $sessionDuration
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::setUser()} instead.
     */
    public function setUser(?User $user, ?int $sessionDuration = null): void
    {
        app(\CraftCms\Cms\Auth\Auth::class)->setUser($user);
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

        return TemplateMode::with(TemplateMode::Cp, function() use ($method) {
            return $method->getAuthFormHtml();
        });
    }

    /**
     * Authenticates the user.
     *
     * Any arguments
     *
     * @param class-string<AuthMethodInterface> $methodClass
     * @param mixed $args,...
     * @return bool
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::verifyMethod} instead.
     */
    public function verify(string $methodClass, mixed ...$args): bool
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->verifyMethod($methodClass, ...$args);
    }

    /**
     * Returns an authentication error message based on the authentication error value.
     * If a default message was passed and the authentication error value is for invalid credentials,
     * that default message will be used.
     *
     * @param string|null $defaultMessage
     * @return string
     * @since 5.7.11
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getAuthMethodErrorMessage} instead.
     */
    public function getAuthErrorMessage(?string $defaultMessage = null): string
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getAuthMethodErrorMessage($defaultMessage);
    }

    /**
     * Returns all available user authentication methods.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getAllMethods} instead.
     */
    public function getAllMethods(?User $user = null): array
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getAllMethods($user)->all();
    }

    /**
     * Returns the authentication methods that are available for the given user.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getAvailableMethods} instead.
     */
    public function getAvailableMethods(?User $user = null): array
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getAvailableMethods($user)->all();
    }

    /**
     * Returns whether any authentication methods are active for the given user.
     *
     * @param User|null $user
     * @return bool
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::hasActiveMethod} instead.
     */
    public function hasActiveMethod(?User $user = null): bool
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->hasActiveMethod($user);
    }

    /**
     * Returns the authentication methods that are active for the given user.
     *
     * @param User|null $user
     * @return AuthMethodInterface[]
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getActiveMethods} instead.
     *
     */
    public function getActiveMethods(?User $user = null): array
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getActiveMethods($user)->all();
    }

    /**
     * Returns an authentication method by its class name.
     *
     * @template T of AuthMethodInterface
     * @param class-string<T> $class
     * @param User|null $user
     * @return T
     * @throws InvalidArgumentException
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::getMethod} instead.
     */
    public function getMethod(string $class, ?User $user = null): AuthMethodInterface
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->getMethod($class, $user);
    }

    /**
     * Returns whether 2FA is required for a user.
     *
     * @param User $user
     * @return bool
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Auth::is2faRequired} instead.
     */
    public function is2faRequired(User $user): bool
    {
        return app(\CraftCms\Cms\Auth\Auth::class)->is2faRequired($user);
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
        if (is_array($requestOptions)) {
            $requestOptions = Json::encode($requestOptions);
        }

        if (!is_string($requestOptions)) {
            $requestOptions = app(Passkeys::class)
                ->webauthnServer()
                ->getSerializer()
                ->serialize($requestOptions, 'json');
        }

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

    /**
     * Returns the WebAuthn server.
     *
     * @deprecated 6.0.0 use {@see Passkeys::webauthnServer} instead.
     */
    public function webauthnServer(): WebauthnServer
    {
        return app(Passkeys::class)->webauthnServer();
    }

    public static function registerEvents(): void
    {
        Event::listen(RegisterAuthMethods::class, function(RegisterAuthMethods $event) {
            if (Craft::$app->getAuth()->hasEventHandlers(self::EVENT_REGISTER_METHODS)) {
                $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->methods->all()]);
                Craft::$app->getAuth()->trigger(self::EVENT_REGISTER_METHODS, $yiiEvent);
                $event->methods = new Collection($yiiEvent->types);
            }
        });

        Event::listen(SettingPassword::class, function(SettingPassword $event) {
            if ($event->status === Password::PASSWORD_RESET) {
                return;
            }

            if (!Craft::$app->getUsers()->isVerificationCodeValidForUser($event->user, $event->code)) {
                return;
            }

            $user = $event->user;
            $user->newPassword = $event->newPassword;
            $user->ruleset->useScenario(UserRules::SCENARIO_PASSWORD);

            if (!Elements::saveElement($user)) {
                $event->status = 'password.save_failed';
                return;
            }

            $event->status = Password::PASSWORD_RESET;
        });
    }
}
