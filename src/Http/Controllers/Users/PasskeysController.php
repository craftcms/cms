<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\PasskeySetupAsset;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class PasskeysController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private Passkeys $passkeys,
    ) {}

    public function index(Request $request, HtmlStack $HtmlStack): CpScreenResponse
    {
        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        $user = $currentUser->asElement();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSKEYS);

        app(InternalAssetRegistry::class)->register(PasskeySetupAsset::class);
        $HtmlStack->js(<<<'JS'
new Craft.PasskeySetup();
JS);

        $response->contentTemplate('users/_passkeys', [
            'user' => $user,
            'passkeys' => $this->passkeys->getPasskeys($user)->all(),
        ]);

        return $response;
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

        $user = $request->craftUser();
        if (! $user) {
            abort(401);
        }

        return $this->asSuccess(t('Passkey created.'), [
            'tableHtml' => $this->passkeyTableHtml($user),
        ]);
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

        return $this->asSuccess(t('Passkey deleted.'), [
            'tableHtml' => $this->passkeyTableHtml($user),
        ]);
    }

    private function passkeyTableHtml(CraftUser $user): string
    {
        return template('users/_passkeys-table', [
            'passkeys' => $this->passkeys->getPasskeys($user)->all(),
        ], templateMode: TemplateMode::Cp);
    }
}
