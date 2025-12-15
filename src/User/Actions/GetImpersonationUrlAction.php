<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use craft\elements\User;
use craft\helpers\UrlHelper;
use CraftCms\Cms\RouteToken\RouteTokens;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\action_url;

final readonly class GetImpersonationUrlAction
{
    public function __construct(
        private RouteTokens $tokens,
    ) {}

    public function __invoke(User $user): string|false
    {
        $token = $this->tokens->createToken([
            action_url('/users/impersonate-with-token'), [
                'userId' => $user->id,
                'prevUserId' => Auth::user()->id ?? $user->id,
            ],
        ], 1, now()->addHour());

        if (! $token) {
            return false;
        }

        $url = $user->can('accessCp') ? UrlHelper::cpUrl() : UrlHelper::siteUrl();

        return UrlHelper::urlWithToken($url, $token);
    }
}
