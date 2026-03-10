<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Auth\OAuth\Exceptions\OAuthException;
use CraftCms\Cms\Auth\OAuth\Exceptions\SocialiteProviderNotFoundException;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Auth\OAuth\OAuthUserResolver;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

use function CraftCms\Cms\t;

final readonly class OAuthController extends AuthenticationController
{
    public function __construct(
        GeneralConfig $generalConfig,
        Auth $auth,
        private OAuth $oAuth,
        private OAuthUserResolver $resolver,
        private Redirector $redirector,
    ) {
        abort_unless(Edition::get()->oAuthAvailable(), 404);

        parent::__construct($generalConfig, $auth);
    }

    public function redirect(Request $request, string $provider): Response
    {
        try {
            $isCpRequest = $request->boolean('cp');

            $defaultReturnUrl = $isCpRequest
                ? UrlHelper::cpUrl($this->generalConfig->getPostCpLoginRedirect())
                : UrlHelper::siteUrl($this->generalConfig->getPostLoginRedirect());

            $this->redirector->setIntendedUrl($request->query('returnUrl', URL::returnUrl($defaultReturnUrl)));

            return $this->oAuth->getProvider($provider)->getDriver()->redirect();
        } catch (SocialiteProviderNotFoundException $exception) {
            throw new HttpException(404, $exception->getMessage(), $exception);
        } catch (Throwable $exception) {
            throw new HttpException(400, $exception->getMessage() ?: t('Unable to initiate an auth request.'), $exception);
        }
    }

    public function callback(Request $request, string $provider): Response
    {
        try {
            $providerConfig = $this->oAuth->getProvider($provider);
            $user = $this->resolver->resolve($providerConfig);

            if ($authError = $this->auth->getAuthError($user)) {
                return $this->handleLoginFailure($request, $authError, $user, $this->loginUrl($request));
            }

            return $this->completeLogin($request, $user, remember: false);
        } catch (SocialiteProviderNotFoundException $exception) {
            throw new HttpException(404, $exception->getMessage(), $exception);
        } catch (Throwable $exception) {
            if ($exception instanceof OAuthException && $exception->authError) {
                return $this->handleLoginFailure($request, $exception->authError, $exception->user, $this->loginUrl($request));
            }

            throw new HttpException(400, $exception->getMessage() ?: t('Unable to complete the auth request.'), $exception);
        }
    }

    private function loginUrl(Request $request): ?string
    {
        if ($request->isCpRequest() || $request->boolean('cp')) {
            return UrlHelper::cpUrl(CpAuthPath::Login->value);
        }

        $loginPath = $this->generalConfig->getLoginPath();

        return $loginPath ? UrlHelper::siteUrl($loginPath) : null;
    }
}
