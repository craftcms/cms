<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PasskeyController extends AuthenticationController
{
    public function requestOptions(Passkeys $passkeys): JsonResponse
    {
        $serializer = $passkeys->webauthnServer()->getSerializer();

        return new JsonResponse([
            'options' => $serializer->serialize($passkeys->getPasskeyRequestOptions(), 'json'),
        ]);
    }

    public function login(Request $request, AuthMethods $auth, Impersonation $impersonation): Response
    {
        $request->validate([
            'requestOptions' => ['required'],
            'authResponse' => ['required'],
        ]);

        $requestOptions = $request->input('requestOptions');
        $response = $request->input('authResponse');
        $credential = WebAuthn::where('credentialId', Json::decode($response)['id'])->first();

        if ($credential === null) {
            return $this->asFailure(t('Passkey authentication failed.'));
        }

        $user = User::findOne(['id' => $credential->userId]);

        if ($user === null) {
            return $this->handleLoginFailure($request);
        }

        if (! $auth->authenticateWithPasskey($user, $requestOptions, $response)) {
            return $this->handleLoginFailure($request, $auth->authError, $user);
        }

        // if we're impersonating, pass the user we're impersonating to the complete method
        if ($impersonation->isImpersonating()) {
            $user = $request->user();
        }

        if ($request->user()) {
            if ($request->user()->id !== $user->id) {
                return $this->handleLoginFailure($request, null, $user);
            }

            $this->confirmPassword();

            return $this->asJsonSuccess(data: [
                'elevatedSessionExpiresAt' => $this->elevatedSessionExpiresAt(),
            ]);
        }

        return $this->completeLogin($request, $user, true);
    }
}
