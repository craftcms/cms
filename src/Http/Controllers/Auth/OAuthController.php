<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\OAuth\Exceptions\OAuthException;
use CraftCms\Cms\Auth\OAuth\Exceptions\SocialiteProviderNotFoundException;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Auth\OAuth\OAuthUserResolver;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Json;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Timebox;
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
        private OAuth $socialite,
        private OAuthUserResolver $resolver,
        private Redirector $redirector,
    ) {
        abort_unless(Edition::get()->oAuthAvailable(), 404);

        parent::__construct($generalConfig, $auth);
    }

    public function redirect(Request $request, string $provider): Response
    {
        try {
            $this->socialite->getProvider($provider);
            $isCpRequest = $request->boolean('cp');
            $defaultReturnUrl = $isCpRequest
                ? UrlHelper::cpUrl($this->generalConfig->getPostCpLoginRedirect())
                : UrlHelper::siteUrl($this->generalConfig->getPostLoginRedirect());

            $this->redirector->setIntendedUrl($request->query('returnUrl', URL::returnUrl($defaultReturnUrl)));

            return $this->socialite->redirect($provider);
        } catch (SocialiteProviderNotFoundException $exception) {
            throw new HttpException(404, $exception->getMessage(), $exception);
        } catch (Throwable $exception) {
            throw new HttpException(400, $exception->getMessage() ?: t('Unable to initiate an auth request.'), $exception);
        }
    }

    public function callback(Request $request, string $provider): Response
    {
        try {
            $providerConfig = $this->socialite->getProvider($provider);
            $profile = $this->socialite->user($provider);
            $user = $this->resolver->resolveAndLogin($providerConfig, $profile);

            return $this->handleSuccessfulLogin($request, $user);
        } catch (SocialiteProviderNotFoundException $exception) {
            throw new HttpException(404, $exception->getMessage(), $exception);
        }
    }
}
