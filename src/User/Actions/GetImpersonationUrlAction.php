<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\currentUser;

readonly class GetImpersonationUrlAction
{
    public function __construct(
        private RouteTokens $tokens,
    ) {}

    public function __invoke(User $user): string|false
    {
        $token = $this->tokens->createToken([
            action_url('/users/impersonate-with-token'), [
                'userId' => $user->id,
                'prevUserId' => currentUser()?->getCraftUserId() ?? $user->id,
            ],
        ], 1, now()->addHour());

        if (! $token) {
            return false;
        }

        $url = $user->can('accessCp') ? Url::cpUrl() : Url::siteUrl();

        return Url::urlWithToken($url, $token);
    }
}
