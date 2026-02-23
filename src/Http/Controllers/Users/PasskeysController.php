<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\passkeysetup\PasskeySetupAsset;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

final readonly class PasskeysController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private Passkeys $passkeys
    ) {}

    public function index(Request $request): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSKEYS);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(PasskeySetupAsset::class);
        AssetRegistry::js(<<<'JS'
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

        return new JsonResponse([
            'options' => $this->passkeys->getPasskeyCreationOptions($request->user()),
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

        return $this->asSuccess(t('Passkey created.'), [
            'tableHtml' => $this->passkeyTableHtml($request->user()),
        ]);
    }

    public function delete(Request $request): Response
    {
        $uid = $request->validate([
            'uid' => ['required', 'string'],
        ])['uid'];

        $this->passkeys->deletePasskey($request->user(), $uid);

        return $this->asSuccess(t('Passkey deleted.'), [
            'tableHtml' => $this->passkeyTableHtml($request->user()),
        ]);
    }

    private function passkeyTableHtml(User $user): string
    {
        return template('users/_passkeys-table', [
            'passkeys' => $this->passkeys->getPasskeys($user)->all(),
        ], templateMode: TemplateMode::Cp);
    }
}
