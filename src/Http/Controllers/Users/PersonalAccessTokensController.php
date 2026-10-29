<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserTokensViewModel;
use CraftCms\Cms\User\EditUserScreens;
use DateInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * The current user’s personal access tokens and authorized apps.
 *
 * @since 6.0.0
 */
readonly class PersonalAccessTokensController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function __construct(
        private PersonalAccessTokens $tokens,
    ) {}

    public function index(Request $request): CpScreenResponse
    {
        if (! $currentUser = $request->craftUser()) {
            abort(401);
        }

        return $this->asEditUserScreen($currentUser->asElement(), EditUserScreens::TOKENS)
            ->inertiaPage('users/Tokens', new UserTokensViewModel($currentUser->asElement(), $this->tokens));
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireConfirmedPassword();

        if (! $user = $request->craftUser()) {
            abort(401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', 'string'],
            'scopes' => ['required', 'array', 'list'],
            'scopes.*' => ['string'],
            'expiresIn' => ['required', 'integer', Rule::in([7, 30, 90, 365])],
        ], [
            'scopes.required' => t('Choose at least one scope.'),
        ]);

        try {
            $token = $this->tokens->create(
                $user->asElement(),
                $validated['group'],
                $validated['name'],
                $validated['scopes'],
                new DateInterval("P{$validated['expiresIn']}D"),
            );
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['scopes' => t('Choose at least one scope.')]);
        }

        return new JsonResponse([
            'token' => $token,
            'message' => t('Token created.'),
        ]);
    }

    public function destroy(Request $request): Response
    {
        if (! $user = $request->craftUser()) {
            abort(401);
        }

        $validated = $request->validate([
            'group' => ['required', 'string'],
            'clientId' => ['required', 'string'],
            'tokenId' => ['nullable', 'string'],
        ]);

        $this->tokens->revoke($user->asElement(), $validated['group'], $validated['clientId'], $validated['tokenId'] ?? null);

        return $this->asSuccess(t('Access revoked.'));
    }
}
