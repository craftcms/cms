<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
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

    public function callback(Request $request, string $provider, OAuth $oauthManager, Users $users): Response
    {
        abort_if(! $definition = $oauthManager->getProviderDefinition($provider), 404);

        $isCpRequest = $this->isCpRequest($request);

        try {
            $socialiteUser = $oauthManager->buildProvider($definition, $isCpRequest)->user();
            $identity = $oauthManager->resolveIdentity($definition, $socialiteUser);
            $user = $oauthManager->resolveUser($definition, $socialiteUser, $identity);

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

            if ($isNew && $definition->activatesUsers) {
                $user->active = true;
                $user->pending = false;
            }

            if (! Craft::$app->getElements()->saveElement($user, false)) {
                throw new InvalidElementException($user);
            }

            if (
                ! $isNew &&
                $definition->activatesUsers &&
                $user->getStatus() !== User::STATUS_ACTIVE
            ) {
                $users->activateUser($user);
            }

            $oauthManager->linkIdentity($user, $definition, $identity);

            if ($isNew) {
                $groupIds = $oauthManager->resolveGroupIds($definition, $socialiteUser, $user, $identity);

                if ($groupIds !== []) {
                    $users->assignUserToGroups($user->id, $groupIds);
                }
            }

            if ($authError = $this->getAuthError($user, $isCpRequest)) {
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
                implode(', ', $user->getErrorSummary(true)) ?: t('Unable to save the user.'),
                previous: $e,
            );
        } catch (Throwable $e) {
            return $this->failedResponse($isCpRequest, t('Authentication failed.'), previous: $e);
        }
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

        Flash::fail($message);

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
            ? UrlHelper::cpUrl($this->generalConfig->getPostCpLoginRedirect())
            : UrlHelper::siteUrl($this->generalConfig->getPostLoginRedirect());
    }

    private function loginUrl(bool $isCpRequest): string
    {
        if ($isCpRequest) {
            return cp_url(CpAuthPath::Login->value);
        }

        if (! $loginPath = $this->generalConfig->getLoginPath()) {
            throw new RuntimeException('The loginPath config setting is disabled.');
        }

        return UrlHelper::siteUrl($loginPath);
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
            default => null,
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

        if (
            app()->isLive() === false &&
            $user->can('accessCpWhenSystemIsOff') === false
        ) {
            return AuthError::NoCpOfflineAccess;
        }

        return null;
    }
}
