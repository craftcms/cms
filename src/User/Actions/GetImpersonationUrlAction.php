<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\currentUser;

/**
 * @since 6.0.0
 */
readonly class GetImpersonationUrlAction
{
    public function __construct(
        private RouteTokens $tokens,
    ) {}

    public function __invoke(User $user): string|false
    {
        // The front end's action path, which the route is under, wherever the
        // URL is made from.
        $token = $this->tokens->createToken([
            ActionRoute::uriForSegments(['users', 'impersonate-with-token'], false), [
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
