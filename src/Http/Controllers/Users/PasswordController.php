<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class PasswordController
{
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request): CpScreenResponse
    {
        $user = $request->user();

        $response = $this->asEditUserScreen($user, self::SCREEN_PASSWORD);

        Craft::$app->getView()->registerAssetBundle(AuthMethodSetupAsset::class);

        $response->action('users/save-password');
        $response->contentTemplate('users/_password', compact('user'));

        return $response;
    }

    public function requireReset(Request $request, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $users, required: true);
    }

    public function removeResetRequirement(Request $request, Users $users): Response
    {
        return $this->togglePasswordResetRequirement($request, $users, required: false);
    }

    private function togglePasswordResetRequirement(Request $request, Users $users, bool $required): Response
    {
        $this->requirePermission('administrateUsers');

        $validated = $request->validate([
            'userId' => ['required', 'int', Rule::exists(Table::USERS, 'id')],
        ]);

        $user = $users->getUserById($validated['userId']);

        abort_if(is_null($user), 400, 'User not found');

        $user->passwordResetRequired = $required;

        if (! Craft::$app->getElements()->saveElement($user, false)) {
            return $this->asFailure(t('Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ]));
        }

        return $this->asSuccess(t('{type} saved.', [
            'type' => User::displayName(),
        ]));
    }
}
