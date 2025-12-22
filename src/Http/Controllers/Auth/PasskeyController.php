<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use Craft;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class PasskeyController extends AuthenticationController
{
    public function requestOptions(): JsonResponse
    {
        return new JsonResponse([
            'options' => Craft::$app->getAuth()->getPasskeyRequestOptions(),
        ]);
    }

    public function login(Request $request, Impersonation $impersonation): Response
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

        if (! $user->authenticateWithPasskey($requestOptions, $response)) {
            return $this->handleLoginFailure($request, $user->authError, $user);
        }

        // if we're impersonating, pass the user we're impersonating to the complete method
        if ($impersonation->isImpersonating()) {
            $user = Auth::user();
        }

        return $this->completeLogin($request, $user, true);
    }
}
