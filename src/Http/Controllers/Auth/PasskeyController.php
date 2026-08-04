<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Closure;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\LoginRateLimiter;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PasskeyController extends AuthenticationController
{
    public function requestOptions(Passkeys $passkeys): JsonResponse
    {
        $serializer = $passkeys->webauthnServer()->getSerializer();
        $serializedData = $serializer->serialize($passkeys->getPasskeyRequestOptions(), 'json');

        Session::put($passkeys->passkeyRequestOptionsParam, $serializedData);

        return new JsonResponse([
            'options' => $serializedData,
        ]);
    }

    public function login(Request $request, Passkeys $passkeys, AuthMethods $auth, Impersonation $impersonation, LoginRateLimiter $loginRateLimiter): Response
    {
        $request->validate([
            'requestOptions' => ['bail', 'required', 'string', 'json'],
            'authResponse' => [
                'bail',
                'required',
                'string',
                'json',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $response = Json::decode($value);

                    if (! is_array($response) || ! isset($response['id']) || ! is_string($response['id']) || $response['id'] === '') {
                        $fail(t('The auth response must contain a credential ID.'));
                    }
                },
            ],
        ]);

        $requestOptions = Session::remove($passkeys->passkeyRequestOptionsParam);

        if (! $requestOptions) {
            return $this->asFailure(t('Passkey authentication failed.'));
        }

        $response = $request->input('authResponse');
        $credential = WebAuthn::where('credentialId', Json::decode($response)['id'])->first();

        if ($credential === null) {
            return $this->asFailure(t('Passkey authentication failed.'));
        }

        /** @var SessionGuard $guard */
        $guard = auth();
        $user = $guard->getProvider()->retrieveById($credential->userId);

        if (! $user instanceof CraftUser) {
            return $this->handleLoginFailure($request);
        }

        if (! $auth->authenticateWithPasskey($user, $requestOptions, $response)) {
            return $this->handleLoginFailure($request, $auth->authError, $user);
        }

        $loginRateLimiter->clear($request);

        // if we're impersonating, pass the user we're impersonating to the complete method
        if ($impersonation->isImpersonating()) {
            $user = $request->craftUser() ?? $user;
        }

        return $this->completeLogin($request, $user, true);
    }
}
