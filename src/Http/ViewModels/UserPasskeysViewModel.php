<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\User\Elements\User;

class UserPasskeysViewModel extends ViewModel
{
    /**
     * The user's passkeys, as structured data for the native Vue listing.
     *
     * @var array<int, array{
     *     uid: string,
     *     name: string,
     *     dateLastUsed: string|null,
     * }>
     */
    public array $passkeys;

    public function __construct(User $user, Passkeys $passkeys)
    {
        $this->passkeys = $passkeys->getPasskeys($user)
            ->map(fn (array $passkey): array => [
                'uid' => $passkey['uid'],
                'name' => $passkey['credentialName'],
                'dateLastUsed' => $passkey['dateLastUsed']?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
