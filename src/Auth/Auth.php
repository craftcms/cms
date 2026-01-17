<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use craft\helpers\DateTimeHelper;
use craft\web\twig\AllowedInSandbox;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\Authenticating;
use CraftCms\Cms\Auth\Events\RegisterAuthMethods;
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
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;
use Webauthn\PublicKeyCredentialRequestOptions;

use function CraftCms\Cms\t;

#[Scoped]
final class Auth
{
    public private(set) ?AuthError $authError = null;

    /**
     * @var Collection<int, Collection<AuthMethodInterface>>
     */
    private Collection $methods;

    /**
     * The user being logged in.
     */
    private ?User $user = null;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Users $users,
        private readonly Hasher $hasher,
        private readonly Passkeys $passkeys,
        private readonly ProjectConfig $projectConfig,
        private readonly Impersonation $impersonation,
    ) {
        $this->methods = new Collection;
    }

    /**
     * @return \Illuminate\Support\Collection<AuthMethodInterface>
     */
    public function getAllMethods(?User $user = null): Collection
    {
        $user ??= auth('craft')->user() ?? $this->getUser();

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

        if (Event::hasListeners(RegisterAuthMethods::class)) {
            Event::dispatch(new RegisterAuthMethods($methods));
        }

        $this->methods[$user->id] = $methods->map(function (string $class) use ($user) {
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
     * @return \Illuminate\Support\Collection<AuthMethodInterface>
     */
    public function getAvailableMethods(?User $user = null): Collection
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
     * @return Collection<AuthMethodInterface>
     */
    public function getActiveMethods(?User $user = null): Collection
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
    public function getMethod(string $class, ?User $user = null): AuthMethodInterface
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

    public function setUser(?User $user): void
    {
        $this->user = $user;

        if ($user) {
            Session::put('user.id', $user->id);
        } else {
            Session::forget('user.id');
        }
    }

    public function is2faRequired(User $user): bool
    {
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

    public function authenticate(User $user, #[SensitiveParameter] array $credentials): bool
    {
        $this->authError = null;

        if (Event::hasListeners(Authenticating::class)) {
            Event::dispatch($event = new Authenticating($credentials));

            $this->authError = $event->authError;

            if (isset($this->authError)) {
                return false;
            }

            if (! $event->performAuthentication) {
                return true;
            }
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

            $this->handleInvalidLogin($user);

            return false;
        }

        $this->authError = $this->getAuthError($user);

        if (! is_null($this->authError)) {
            return false;
        }

        return true;
    }

    public function authenticateWithPasskey(User $user, PublicKeyCredentialRequestOptions|array|string $requestOptions, string $response): bool
    {
        $this->authError = null;

        if (Event::hasListeners(Authenticating::class)) {
            Event::dispatch($event = new Authenticating);

            $this->authError = $event->authError;

            if (isset($this->authError)) {
                return false;
            }

            if (! $event->performAuthentication) {
                return true;
            }
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
        } catch (InvalidArgumentException) {
            $keyValid = false;
        }

        if (! $keyValid) {
            $this->handleInvalidLogin($user);

            return false;
        }

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
            $this->setUser(null);

            // if we're impersonating, pass the user we're impersonating to the complete the login
            if ($this->impersonation->isImpersonating()) {
                /** @var User $user */
                $user = auth('craft')->user();
            }

            auth('craft')->login($user, true);
        }

        return true;
    }

    public function getAuthError(User $user): ?AuthError
    {
        switch ($user->getStatus()) {
            case User::STATUS_INACTIVE:
            case User::STATUS_ARCHIVED:
                return AuthError::InvalidCredentials;
            case User::STATUS_PENDING:
                return AuthError::PendingVerification;
            case User::STATUS_SUSPENDED:
                return AuthError::AccountSuspended;
            case User::STATUS_ACTIVE:
                if ($user->locked) {
                    // Let them know how much time they have to wait (if any) before their account is unlocked.
                    if (Cms::config()->cooldownDuration) {
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
        }

        return null;
    }

    public function getAuthMethodErrorMessage(?string $defaultMessage = null): string
    {
        $user = $this->getUser();
        $authError = null;

        if ($user) {
            $authError = $this->getAuthError($user);
        }

        if ($authError === AuthError::InvalidCredentials || ! $authError) {
            return $defaultMessage ?? t('Invalid verification code.');
        }

        [, $message] = $this->getLoginFailureInfo($authError, $user);

        return $message;
    }

    /**
     * @return array{0:AuthError|null,1:string}
     */
    public function getLoginFailureInfo(?AuthError $authError, ?User $user): array
    {
        if (Cms::config()->preventUserEnumeration && in_array($authError, [AuthError::AccountLocked, AuthError::AccountCooldown])) {
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
            default => Cms::config()->useEmailAsUsername
                ? t('Invalid email or password.')
                : t('Invalid username or password.'),
        };

        return [$authError, $message];
    }

    public function handleInvalidLogin(User $user): void
    {
        $this->users->handleInvalidLogin($user);

        // Was that one bad password/2fa code/passkey too many?
        if ($user->locked && ! Cms::config()->preventUserEnumeration) {
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

    public function setRememberedUsername(User $user): void
    {
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
