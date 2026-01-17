<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\passkeysetup\PasskeySetupAsset;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\Request;

final readonly class PasskeysController
{
    use EditUserTrait;

    public function index(Request $request, Passkeys $passkeys): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSKEYS);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(PasskeySetupAsset::class);
        $view->registerJs(<<<'JS'
new Craft.PasskeySetup();
JS);

        $response->contentTemplate('users/_passkeys', [
            'user' => $user,
            'passkeys' => $passkeys->getPasskeys($user)->all(),
        ]);

        return $response;
    }
}
