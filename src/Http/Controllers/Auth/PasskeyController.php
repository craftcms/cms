<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class PasskeyController extends AuthenticationController
{
    public function requestOptions(Passkeys $passkeys): JsonResponse
    {
        return new JsonResponse([
            'options' => $passkeys->getPasskeyRequestOptions(),
        ]);
    }

    public function login(Request $request, Auth $auth, Impersonation $impersonation): Response
    {
        $request->validate([
            'requestOptions' => ['required'],
            'response' => ['required'],
        ]);

        $requestOptions = $request->input('requestOptions');
        $response = $request->input('response');
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

        return $this->completeLogin($request, $user, true);
    }
}
