<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserPasskeysViewModel;
use CraftCms\Cms\User\EditUserScreens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PasskeysController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private Passkeys $passkeys,
    ) {}

    public function index(Request $request): CpScreenResponse
    {
        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        $user = $currentUser->asElement();

        return $this->asEditUserScreen($user, EditUserScreens::PASSKEYS)
            ->inertiaPage('users/Passkeys', new UserPasskeysViewModel($user, $this->passkeys));
    }

    public function creationOptions(Request $request): JsonResponse
    {
        $this->requireConfirmedPassword();
        $serializer = $this->passkeys->webauthnServer()->getSerializer();
        $user = $request->craftUser();
        if (! $user) {
            abort(401);
        }

        return new JsonResponse([
            'options' => $serializer->serialize($this->passkeys->getPasskeyCreationOptions($user), 'json'),
        ]);
    }

    public function verifyCreation(Request $request): Response
    {
        $this->requireConfirmedPassword();

        $request->validate([
            'credentials' => ['required'],
            'credentialName' => ['nullable', 'string'],
        ]);

        $verified = $this->passkeys->verifyPasskeyCreationResponse(
            $request->input('credentials'),
            $request->input('credentialName'),
        );

        if (! $verified) {
            return $this->asFailure(t('Passkey creation failed.'));
        }

        return $this->asSuccess(t('Passkey created.'));
    }

    public function delete(Request $request): Response
    {
        $uid = $request->validate([
            'uid' => ['required', 'string'],
        ])['uid'];

        $user = $request->craftUser();
        if (! $user) {
            abort(401);
        }

        $this->passkeys->deletePasskey($user, $uid);

        return $this->asSuccess(t('Passkey deleted.'));
    }
}
