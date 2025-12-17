<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\passkeysetup\PasskeySetupAsset;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\Request;

final readonly class PasskeysController
{
    use EditUserTrait;

    public function index(Request $request): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSKEYS);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(PasskeySetupAsset::class);
        $view->registerJs(<<<'JS'
new Craft.PasskeySetup();
JS);

        $passkeys = Craft::$app->getAuth()->getPasskeys($user);
        $response->contentTemplate('users/_passkeys', [
            'user' => $user,
            'passkeys' => $passkeys,
        ]);

        return $response;
    }
}
