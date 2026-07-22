<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\AuthMethodsResolving;
use CraftCms\Cms\Auth\Events\UserAuthenticating;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;
use Webauthn\Exception\InvalidUserHandleException;

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

#[Scoped]
class AuthMethods
{
    public private(set) ?AuthError $authError = null;

    /**
     * @var Collection<int, Collection<AuthMethodInterface>>
     */
    private Collection $methods;

    /**
     * The user element being logged in.
     */
    private ?User $user = null;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Users $users,
        private readonly Hasher $hasher,
        private readonly Passkeys $passkeys,
        private readonly ProjectConfig $projectConfig,
    ) {
        $this->methods = new Collection;
    }

    /**
     * @return Collection<AuthMethodInterface>
     */
    public function getAllMethods(?CraftUser $user = null): Collection
    {
        $user = $user?->asElement()
            ?? currentUserElement()
            ?? $this->getUser();

        if (! $user?->id) {
            return new Collection;
        }

        if (isset($this->methods[$user->id])) {
            return $this->methods[$user->id];
        }

        $methods = new Collection([
            TOTP::class,
            RecoveryCodes::class,
        ]);

        event($event = new AuthMethodsResolving($methods));

        $this->methods[$user->id] = $event->methods->map(function (string $class) use ($user) {
            if (! is_subclass_of($class, AuthMethodInterface::class)) {
                throw new RuntimeException("$class must implement ".AuthMethodInterface::class);
            }

            /** @var AuthMethodInterface $method */
            $method = app()->make($class);
            $method->setUser($user);

            return $method;
        });

        $this->methods[$user->id] = $this->methods[$user->id]->sort(function (AuthMethodInterface $a, AuthMethodInterface $b) {
            // place Recovery Codes at the end
            if ($a instanceof RecoveryCodes) {
                return 1;
            }

            if ($b instanceof RecoveryCodes) {
                return -1;
            }

            return $a::displayName() <=> $b::displayName();
        });

        return $this->methods[$user->id];
    }

    /**
     * @return Collection<AuthMethodInterface>
     */
    public function getAvailableMethods(?CraftUser $user = null): Collection
    {
        $methods = $this->getAllMethods($user);

        // only include Recovery Codes if at least one other method is active
        $hasActiveMethod = $methods->contains(
            fn (AuthMethodInterface $method) => ! $method instanceof RecoveryCodes && $method->isActive(),
        );

        if ($hasActiveMethod) {
            return $methods;
        }

        return $methods
            ->reject(fn (AuthMethodInterface $method) => $method instanceof RecoveryCodes)
            ->values();
    }

    /**
     * Returns whether any authentication methods are active for the given user.
     */
    public function hasActiveMethod(?CraftUser $user = null): bool
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
     * @return Collection<AuthMethodInterface>
     */
    public function getActiveMethods(?CraftUser $user = null): Collection
    {
        return $this->getAvailableMethods($user)
            ->filter(fn (AuthMethodInterface $method) => $method->isActive())
            ->values();
    }

    /**
     * Returns an authentication method by its class name.
     *
     * @template T of AuthMethodInterface
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws InvalidArgumentException
     */
    public function getMethod(string $class, ?CraftUser $user = null): AuthMethodInterface
    {
        foreach ($this->getAllMethods($user) as $method) {
            if ($method::class === $class) {
                return $method;
            }
        }

        throw new InvalidArgumentException("Invalid authentication method: $class");
    }

    public function getUser(): ?User
    {
        if (isset($this->user)) {
            return $this->user;
        }

        $userId = Session::get('user.id');

        if ($userId) {
            $this->user = User::findOne($userId);
        }

        return $this->user;
    }

    public function setUser(?CraftUser $user, bool $remember = false, ?CraftUser $loginUser = null): void
    {
        $this->user = $user?->asElement();

        if ($this->user) {
            Session::put('user.id', $this->user->id);
            Session::put('user.login_id', ($loginUser ?? $user)->getCraftUserId());
            Session::put('user.remember', $remember);
            Session::put('user.pending_2fa_at', now()->timestamp);
        } else {
            Session::forget(['user.id', 'user.login_id', 'user.remember', 'user.pending_2fa_at']);
        }
    }

    public function is2faRequired(CraftUser $user): bool
    {
        $user = $user->asElement();

        if (Edition::get() === Edition::Solo) {
            return false;
        }

        $require2fa = $this->projectConfig->get(sprintf('%s.require2fa', ProjectConfig::PATH_USERS));

        if ($require2fa === 'all') {
            return true;
        }

        if (is_array($require2fa)) {
            $groups = Arr::pluck(array: $user->getGroups(), value: 'uid', key: 'uid');

            foreach ($require2fa as $group) {
                if ($group === 'admins') {
                    if ($user->isAdmin()) {
                        return true;
                    }
                } elseif (isset($groups[$group])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function authenticate(CraftUser $user, #[SensitiveParameter] array $credentials): bool
    {
        event($event = new UserAuthenticating($credentials));

        $user = $user->asElement();

        $this->authError = $event->authError;

        if (isset($this->authError)) {
            return false;
        }

        if (! $event->performAuthentication) {
            return true;
        }

        if (is_null($plain = $credentials['password'])) {
            $this->authError = AuthError::InvalidCredentials;

            $this->handleInvalidLogin($user);

            return false;
        }

        if (is_null($hashed = $user->getAuthPassword())) {
            $this->authError = AuthError::InvalidCredentials;

            $this->handleInvalidLogin($user);

            return false;
        }

        if (! $this->hasher->check($plain, $hashed)) {
            $this->authError = AuthError::InvalidCredentials;

            return false;
        }

        $this->authError = $this->getAuthError($user);

        if (! is_null($this->authError)) {
            return false;
        }

        return true;
    }

    public function authenticateWithPasskey(CraftUser $user, string $requestOptions, string $response): bool
    {
        event($event = new UserAuthenticating);

        $user = $user->asElement();

        $this->authError = $event->authError;

        if (isset($this->authError)) {
            return false;
        }

        if (! $event->performAuthentication) {
            return true;
        }

        // make sure the passkey exists and belongs to this user
        $credential = WebAuthn::where('credentialId', Json::decode($response)['id'])->first();

        if (! $credential || $credential->userId !== $user->id) {
            $this->authError = AuthError::InvalidCredentials;

            return false;
        }

        // Validate the security key
        try {
            $keyValid = $this->passkeys->verifyPasskey($user, $requestOptions, $response);
        } catch (InvalidUserHandleException) {
            $keyValid = $this->passkeys->verifyPasskey($user, $requestOptions, $response, checkOldUserHandle: true);
        } catch (InvalidArgumentException) {
            $keyValid = false;
        }

        $updatedCredentialRecord = Session::remove($this->passkeys->passkeyCredSourceParam);

        if (! $keyValid) {
            $this->authError = AuthError::InvalidCredentials;

            return false;
        }

        $this->passkeys->webauthnServer()->getCredentialRepository()->saveCredentialSource($updatedCredentialRecord);

        $this->authError = $this->getAuthError($user);

        return is_null($this->authError);
    }

    public function verifyMethod(string $methodClass, mixed ...$args): bool
    {
        $user = $this->getUser();

        if (! $this->getMethod($methodClass, $user)->verify(...$args)) {
            if ($user) {
                $this->handleInvalidLogin($user);
            }

            return false;
        }

        // success!
        if ($user) {
            $user = User::findOne($user->id);
            $this->authError = $user
                ? $this->getAuthError($user)
                : AuthError::InvalidCredentials;

            if ($this->authError) {
                $this->setUser(null);

                return false;
            }

            $authUser = auth()->getProvider()->retrieveById(Session::get('user.login_id', $user->id));
            $remember = (bool) Session::get('user.remember', false);

            $this->setUser(null);

            if (! $authUser) {
                return false;
            }

            auth()->login($authUser, $remember);
        }

        return true;
    }

    public function getAuthError(CraftUser $user): ?AuthError
    {
        $user = $user->asElement();

        switch ($user->getStatus()) {
            case User::STATUS_INACTIVE:
            case User::STATUS_ARCHIVED:
            default:
                return AuthError::InvalidCredentials;
            case User::STATUS_PENDING:
                return AuthError::PendingVerification;
            case User::STATUS_SUSPENDED:
                return AuthError::AccountSuspended;
            case User::STATUS_ACTIVE:
                if ($user->locked) {
                    // Let them know how much time they have to wait (if any) before their account is unlocked.
                    if ($this->generalConfig->cooldownDuration) {
                        return AuthError::AccountCooldown;
                    }

                    return AuthError::AccountLocked;
                }

                // Is a password reset required?
                if ($user->passwordResetRequired) {
                    return AuthError::PasswordResetRequired;
                }

                if (request()->isCpRequest()) {
                    if (! $user->can('accessCp')) {
                        return AuthError::NoCpAccess;
                    }

                    if (
                        app()->isLive() === false &&
                        $user->can('accessCpWhenSystemIsOff') === false
                    ) {
                        return AuthError::NoCpOfflineAccess;
                    }

                    return null;
                }

                if (
                    app()->isLive() === false &&
                    $user->can('accessSiteWhenSystemIsOff') === false
                ) {
                    return AuthError::NoSiteOfflineAccess;
                }

                return null;
        }
    }

    public function getAuthMethodErrorMessage(?string $defaultMessage = null): string
    {
        $user = $this->getUser();
        $authError = $this->authError;

        if (! $authError && $user) {
            $authError = $this->getAuthError($user);
        }

        if (
            $authError === AuthError::InvalidCredentials || ! $authError ||
            // if preventUserEnumeration is true and the account is locked, still show the same message
            (
                Cms::config()->preventUserEnumeration &&
                in_array($authError, [AuthError::AccountLocked, AuthError::AccountCooldown])
            )
        ) {
            return $defaultMessage ?? t('Invalid verification code.');
        }

        [, $message] = $this->getLoginFailureInfo($authError, $user);

        return $message;
    }

    /**
     * @return array{0:AuthError|null,1:string}
     */
    public function getLoginFailureInfo(?AuthError $authError, ?CraftUser $user): array
    {
        $user = $user?->asElement();

        if ($this->generalConfig->preventUserEnumeration && in_array($authError, [AuthError::AccountLocked, AuthError::AccountCooldown])) {
            $authError = AuthError::InvalidCredentials;
        }

        $message = match ($authError) {
            AuthError::PendingVerification => t('Account has not been activated.'),
            AuthError::AccountLocked => t('Account locked.'),
            AuthError::AccountCooldown => ($timeRemaining = $user?->getRemainingCooldownTime())
                ? t('Account locked. Try again in {time}.', ['time' => DateTimeHelper::humanDuration($timeRemaining)])
                : t('Account locked.'),
            AuthError::PasswordResetRequired => $user && $this->users->sendPasswordResetEmail($user)
                ? t('You need to reset your password. Check your email for instructions.')
                : t('You need to reset your password, but an error was encountered when sending the password reset email.'),
            AuthError::AccountSuspended => t('Account suspended.'),
            AuthError::NoCpAccess => t('You cannot access the control panel with that account.'),
            AuthError::NoCpOfflineAccess => t('You cannot access the control panel while the system is offline with that account.'),
            AuthError::NoSiteOfflineAccess => t('You cannot access the site while the system is offline with that account.'),
            default => $this->generalConfig->useEmailAsUsername
                ? t('Invalid email or password.')
                : t('Invalid username or password.'),
        };

        return [$authError, $message];
    }

    public function handleInvalidLogin(CraftUser $user): void
    {
        $user = $user->asElement();

        $this->users->handleInvalidLogin($user);

        // Was that one bad password/2fa code/passkey too many?
        if ($user->locked && ! $this->generalConfig->preventUserEnumeration) {
            // Will set the authError to either AccountCooldown or AccountLocked
            $this->authError = $this->getAuthError($user);
        } else {
            $this->authError = AuthError::InvalidCredentials;
        }
    }

    public function rememberedUsernameCookie(): string
    {
        return config('session.cookie').'_username';
    }

    #[AllowedInSandbox]
    public function getRememberedUsername(): ?string
    {
        return Cookie::get($this->rememberedUsernameCookie());
    }

    public function setRememberedUsername(CraftUser $user): void
    {
        $user = $user->asElement();

        if ($this->generalConfig->rememberUsernameDuration === 0) {
            Cookie::unqueue($this->rememberedUsernameCookie());
            Cookie::forget($this->rememberedUsernameCookie());

            return;
        }

        Cookie::queue(
            name: $this->rememberedUsernameCookie(),
            value: $user->username,
            minutes: floor($this->generalConfig->rememberUsernameDuration / 60),
        );
    }
}
