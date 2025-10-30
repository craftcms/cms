<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Token\Tokens;
use CraftCms\Cms\User\Models\User;

final readonly class GetImpersonationUrlAction
{
    public function __construct(
        private Tokens $tokens,
    ) {}

    public function __invoke(User $user): string|false
    {
        $token = $this->tokens->createToken([
            'users/impersonate-with-token', [
                'userId' => $user->id,
                'prevUserId' => $user->id,
            ],
        ], 1, now()->addHour());

        if (! $token) {
            return false;
        }

        $url = $user->can('accessCp') ? UrlHelper::cpUrl() : UrlHelper::siteUrl();

        return UrlHelper::urlWithToken($url, $token);
    }
}
