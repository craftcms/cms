<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Http\Controllers\Users\SignInProvidersController;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

readonly class OAuthController extends AuthenticationController
{
    private const string CP_CONTEXT_VALUE = 'cp';

    public function redirect(Request $request, Redirector $redirector, string $provider, OAuth $oauthManager): Response
    {
        abort_if(! $definition = $oauthManager->getProviderDefinition($provider), 404);

        $isCpRequest = $request->isCpRequest();

        $redirector->setIntendedUrl(URL::returnUrl(
            $this->defaultReturnUrl($isCpRequest),
        ));

        return $oauthManager->buildProvider($definition, $isCpRequest)->redirect();
    }

    public function callback(Request $request, string $provider, OAuth $oauthManager, Users $users, Elements $elements): Response
    {
        abort_if(! $definition = $oauthManager->getProviderDefinition($provider), 404);

        $isCpRequest = $this->isCpRequest($request);

        try {
            $socialiteUser = $oauthManager->buildProvider($definition, $isCpRequest)->user();
            $identity = $oauthManager->resolveIdentity($definition, $socialiteUser);

            if ($connectRequest = $this->pullConnectRequest($request)) {
                try {
                    $user = $request->craftUser()?->asElement();

                    if ($user && ($authError = $this->auth->getMaintenanceAuthError($user, $isCpRequest))) {
                        return $this->connectFailedResponse(
                            $this->auth->getLoginFailureInfo($authError, $user)[1],
                        );
                    }

                    return $this->connectResponse($request, $connectRequest, $definition, $identity, $oauthManager);
                } catch (Throwable $e) {
                    return $this->connectFailedResponse(t('Authentication failed.'), $e);
                }
            }

            $user = $oauthManager->resolveUser($definition, $socialiteUser, $identity);
            $email = $socialiteUser->getEmail();

            if (
                ! $user &&
                ! $definition->trustsEmail &&
                is_string($email) &&
                $oauthManager->findUserByEmail($email)
            ) {
                return $this->failedResponse($isCpRequest, t('Authentication failed.'));
            }

            if (! $user && ! $oauthManager->canCreateUsers($definition)) {
                return $this->failedResponse(
                    $isCpRequest,
                    $definition->createsUsers === false
                        ? t('This OAuth provider cannot create new users.')
                        : t('Public registration is not allowed.'),
                );
            }

            $user ??= new User;
            $isNew = ! isset($user->id);

            $user = $oauthManager->populateUser($definition, $socialiteUser, $user, $identity, $isNew);

            if ($authError = $this->auth->getMaintenanceAuthError($user, $isCpRequest)) {
                return $this->failedResponse(
                    $isCpRequest,
                    $this->auth->getLoginFailureInfo($authError, $user)[1],
                    $authError,
                );
            }

            if ($isNew && $definition->activatesUsers) {
                $user->active = true;
                $user->pending = false;
            }

            if (! $elements->saveElement($user, false)) {
                throw new InvalidElementException($user);
            }

            if (
                ! $isNew &&
                $definition->activatesUsers &&
                $user->getStatus() !== User::STATUS_ACTIVE
            ) {
                $users->activateUser($user);
            }

            $authError = $this->getAuthError($user, $isCpRequest);

            if (! $authError || $isNew) {
                $oauthManager->linkIdentity($user, $definition, $identity);

                if ($isNew) {
                    $groupIds = $oauthManager->resolveGroupIds($definition, $socialiteUser, $user, $identity);

                    if ($groupIds !== []) {
                        $users->assignUserToGroups($user->id, $groupIds);
                    }
                }
            }

            if ($authError) {
                return $this->failedResponse(
                    $isCpRequest,
                    $this->auth->getLoginFailureInfo($authError, $user)[1],
                    $authError,
                );
            }

            return $this->finalizeLogin($request, $user, true, skipTwoFactor: true);
        } catch (InvalidElementException $e) {
            /** @var User $user */
            $user = $e->element;

            return $this->failedResponse(
                $isCpRequest,
                implode(', ', $user->errors()->all()) ?: t('Unable to save the user.'),
                previous: $e,
            );
        } catch (Throwable $e) {
            if ($this->pullConnectRequest($request) !== null) {
                return $this->connectFailedResponse(t('Authentication failed.'), $e);
            }

            return $this->failedResponse($isCpRequest, t('Authentication failed.'), previous: $e);
        }
    }

    /**
     * @param  array{provider?: string, userId?: int}  $connectRequest
     */
    private function connectResponse(
        Request $request,
        array $connectRequest,
        ProviderDefinition $definition,
        string $identity,
        OAuth $oauthManager,
    ): Response {
        if (($connectRequest['provider'] ?? null) !== $definition->handle) {
            return $this->connectFailedResponse(t('Authentication failed.'));
        }

        if ($definition->stateless) {
            return $this->connectFailedResponse(t('This OAuth provider cannot be connected to an account.'));
        }

        $user = $request->craftUser()?->asElement();

        if (! $user || $user->id !== (int) ($connectRequest['userId'] ?? 0)) {
            return $this->connectFailedResponse(t('Authentication failed.'));
        }

        $currentIdentity = $oauthManager->identityFor($user, $definition);

        if ($currentIdentity === $identity) {
            return $this->connectSuccessResponse(t('{provider} is already connected.', [
                'provider' => $definition->name,
            ]));
        }

        if ($currentIdentity !== null) {
            return $this->connectFailedResponse(t('Disconnect {provider} before connecting a different account.', [
                'provider' => $definition->name,
            ]));
        }

        $linkedUserId = $oauthManager->linkedUserId($definition, $identity);

        if ($linkedUserId !== null && $linkedUserId !== $user->id) {
            return $this->connectFailedResponse(t('{provider} is already connected to another user.', [
                'provider' => $definition->name,
            ]));
        }

        $oauthManager->linkIdentity($user, $definition, $identity);

        return $this->connectSuccessResponse(t('{provider} connected.', [
            'provider' => $definition->name,
        ]));
    }

    /**
     * @return array{provider?: string, userId?: int}|null
     */
    private function pullConnectRequest(Request $request): ?array
    {
        $connectRequest = $request->session()->pull(OAuth::CONNECT_SESSION_KEY);

        return is_array($connectRequest) ? $connectRequest : null;
    }

    private function connectSuccessResponse(string $message): Response
    {
        Flash::success($message);

        return to_action([SignInProvidersController::class, 'index'])->with('success', $message);
    }

    private function connectFailedResponse(
        string $message,
        ?Throwable $previous = null,
    ): Response {
        if ($previous) {
            Log::warning($message, [__METHOD__, 'exception' => $previous]);
        }

        Flash::error($message);

        return to_action([SignInProvidersController::class, 'index'])->with('error', $message);
    }

    private function failedResponse(
        bool $isCpRequest,
        string $message,
        ?AuthError $authError = null,
        ?Throwable $previous = null,
    ): Response {
        if ($previous) {
            Log::warning($message, [__METHOD__, 'exception' => $previous]);
        }

        Flash::error($message);

        return redirect($this->loginUrl($isCpRequest))->with(array_filter([
            'error' => $message,
            'errorCode' => $authError?->value,
        ]));
    }

    private function isCpRequest(Request $request): bool
    {
        return $request->query('context') === self::CP_CONTEXT_VALUE;
    }

    private function defaultReturnUrl(bool $isCpRequest): string
    {
        return $isCpRequest
            ? \CraftCms\Cms\Support\Url::cpUrl($this->generalConfig->getPostCpLoginRedirect())
            : \CraftCms\Cms\Support\Url::siteUrl($this->generalConfig->getPostLoginRedirect());
    }

    private function loginUrl(bool $isCpRequest): string
    {
        if ($isCpRequest) {
            return cp_url(CpAuthPath::Login->value);
        }

        if (! $loginPath = $this->generalConfig->getLoginPath()) {
            return cp_url(CpAuthPath::Login->value);
        }

        return \CraftCms\Cms\Support\Url::siteUrl($loginPath);
    }

    private function getAuthError(User $user, bool $isCpRequest): ?AuthError
    {
        if (! $isCpRequest) {
            return $this->auth->getAuthError($user);
        }

        return match ($user->getStatus()) {
            User::STATUS_INACTIVE, User::STATUS_ARCHIVED => AuthError::InvalidCredentials,
            User::STATUS_PENDING => AuthError::PendingVerification,
            User::STATUS_SUSPENDED => AuthError::AccountSuspended,
            User::STATUS_ACTIVE => $this->getCpAuthError($user),
            default => AuthError::InvalidCredentials,
        };
    }

    private function getCpAuthError(User $user): ?AuthError
    {
        if ($user->locked) {
            return $this->generalConfig->cooldownDuration
                ? AuthError::AccountCooldown
                : AuthError::AccountLocked;
        }

        if ($user->passwordResetRequired) {
            return AuthError::PasswordResetRequired;
        }

        if (! $user->can('accessCp')) {
            return AuthError::NoCpAccess;
        }

        return $this->auth->getMaintenanceAuthError($user, true);
    }
}
