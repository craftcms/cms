<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserSignInProvidersViewModel;
use CraftCms\Cms\User\EditUserScreens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class SignInProvidersController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private OAuth $oauth,
    ) {}

    public function index(Request $request): CpScreenResponse
    {
        abort_if($this->oauth->getProviderDefinitions()->isEmpty(), 404);

        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        $user = $currentUser->asElement();

        return $this->asEditUserScreen($user, EditUserScreens::SIGN_IN_PROVIDERS)
            ->inertiaPage('users/SignInProviders', new UserSignInProvidersViewModel($user, $this->oauth));
    }

    public function connect(Request $request, string $provider): JsonResponse
    {
        $this->requireConfirmedPassword(t('An elevated session is required to connect a sign-in provider.'));

        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        abort_if(! $definition = $this->oauth->getProviderDefinition($provider), 404);

        if ($definition->stateless) {
            abort(422, t('This OAuth provider cannot be connected to an account.'));
        }

        $request->session()->put(OAuth::CONNECT_SESSION_KEY, [
            'provider' => $definition->handle,
            'userId' => $currentUser->getCraftUserId(),
        ]);

        return new JsonResponse([
            'url' => $this->oauth->buildProvider($definition, true)->redirect()->getTargetUrl(),
        ]);
    }

    public function destroy(Request $request, string $provider): Response
    {
        $this->requireConfirmedPassword(t('An elevated session is required to disconnect a sign-in provider.'));

        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        abort_if(! $definition = $this->oauth->getProviderDefinition($provider), 404);

        $this->oauth->unlinkIdentity($currentUser->asElement(), $definition);

        return $this->asSuccess(t('{provider} disconnected.', [
            'provider' => $definition->name,
        ]), redirect: action([self::class, 'index']));
    }
}
