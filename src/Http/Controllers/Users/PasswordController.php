<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\Request;

final readonly class PasswordController
{
    use EditUserTrait;

    public function index(Request $request): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSWORD);

        Craft::$app->getView()->registerAssetBundle(AuthMethodSetupAsset::class);

        $response->action('users/save-password');
        $response->contentTemplate('users/_password', compact('user'));

        return $response;
    }
}
