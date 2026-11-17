<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Guidance\Guidance;
use CraftCms\Cms\Http\Controllers\Users\EditUserTrait;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The AI assistant screen of a user’s account, where they write their own guidance.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AccountController
{
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private Guidance $guidance,
    ) {}

    public function index(Request $request): CpScreenResponse
    {
        $user = $this->user($request);

        return $this->asEditUserScreen($user, EditUserScreens::AI_ASSISTANT)
            ->inertiaPage('users/AiAssistant', [
                'guidance' => $this->guidance->forUser($user->id) ?? '',
            ]);
    }

    public function update(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'guidance' => ['present', 'nullable', 'string', 'max:65535'],
        ]);

        $this->guidance->save(siteId: null, userId: $user->id, body: (string) $validated['guidance']);

        return $this->asSuccess(t('Guidance saved.'));
    }

    private function user(Request $request): User
    {
        $user = $request->craftUser() ?? abort(401);

        return $user->asElement();
    }
}
