<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use CraftCms\Cms\Auth\Models\SsoIdentity;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
final class OAuthRepository
{
    public function exists(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return SsoIdentity::query()
            ->where('userId', $userId)
            ->exists();
    }

    public function findUser(string $provider, string|int $identityId): ?User
    {
        $userId = SsoIdentity::query()
            ->where('provider', $provider)
            ->where('identityId', $identityId)
            ->value('userId');

        if (! $userId) {
            return null;
        }

        /** @var User|null */
        return User::find()
            ->id((int) $userId)
            ->status(null)
            ->first();
    }

    public function link(User $user, string $provider, string|int $identityId): bool
    {
        if (! $user->id) {
            return false;
        }

        $identity = SsoIdentity::firstOrNew([
            'provider' => $provider,
            'identityId' => $identityId,
            'userId' => $user->id,
        ]);

        return $identity->save();
    }
}
