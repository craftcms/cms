<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Arrayable;

use function CraftCms\Cms\t;

/**
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
class UserSignInProvidersViewModel implements Arrayable
{
    public bool $readOnly = false;

    /** @var array<int, array{
     *     handle: string,
     *     name: string,
     *     icon: string|null,
     *     connected: bool,
     *     canConnect: bool,
     *     canDisconnect: bool,
     *     disabledReason: string|null,
     *     disconnectWarning: string|null,
     * }>
     */
    public array $providers;

    public function __construct(User $user, OAuth $oauth)
    {
        $this->providers = $oauth->getProviderDefinitions()
            ->map(function (ProviderDefinition $provider) use ($user, $oauth): array {
                $connected = $oauth->identityFor($user, $provider) !== null;
                $canDisconnect = $connected && $oauth->canUnlinkIdentity($user, $provider);

                return [
                    'handle' => $provider->handle,
                    'name' => $provider->name,
                    'icon' => $provider->icon,
                    'connected' => $connected,
                    'canConnect' => ! $connected && ! $provider->stateless,
                    'canDisconnect' => $canDisconnect,
                    'disabledReason' => $this->disabledReason($provider, $connected, $canDisconnect),
                    'disconnectWarning' => $canDisconnect ? t('Are you sure you want to disconnect {provider}?', [
                        'provider' => $provider->name,
                    ]) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function toArray(): array
    {
        return [
            'readOnly' => $this->readOnly,
            'providers' => $this->providers,
        ];
    }

    private function disabledReason(ProviderDefinition $provider, bool $connected, bool $canDisconnect): ?string
    {
        if ($connected) {
            return $canDisconnect ? null : t('You can’t disconnect {provider} because it’s the only way to sign in to your account.', [
                'provider' => $provider->name,
            ]);
        }

        if (! $provider->stateless) {
            return null;
        }

        return t('This provider can’t be connected because it is configured to use stateless OAuth.');
    }
}
