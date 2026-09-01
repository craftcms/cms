<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Arrayable;

use function CraftCms\Cms\t;

/** @implements Arrayable<string, mixed> */
class UserSignInProvidersViewModel implements Arrayable
{
    public bool $readOnly = false;

    /** @var array<int, array{
     *     handle: string,
     *     name: string,
     *     icon: string|null,
     *     connected: bool,
     *     canConnect: bool,
     *     disabledReason: string|null,
     *     disconnectWarning: string|null,
     * }>
     */
    public array $providers;

    public function __construct(User $user, OAuth $oauth)
    {
        $providers = $oauth->getProviderDefinitions();

        $connectedProviderCount = $providers
            ->filter(fn (ProviderDefinition $provider): bool => $oauth->identityFor($user, $provider) !== null)
            ->count();

        $this->providers = $providers
            ->map(function (ProviderDefinition $provider) use ($user, $oauth, $connectedProviderCount): array {
                $connected = $oauth->identityFor($user, $provider) !== null;
                $lastPrimaryCredential = $connected && ! $user->getHasPassword() && $connectedProviderCount === 1;

                return [
                    'handle' => $provider->handle,
                    'name' => $provider->name,
                    'icon' => $provider->icon,
                    'connected' => $connected,
                    'canConnect' => ! $connected && ! $provider->stateless,
                    'disabledReason' => $this->disabledReason($provider, $connected),
                    'disconnectWarning' => $connected ? $this->disconnectWarning($provider, $lastPrimaryCredential) : null,
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

    private function disabledReason(ProviderDefinition $provider, bool $connected): ?string
    {
        if ($connected || ! $provider->stateless) {
            return null;
        }

        return t('This provider can’t be connected because it is configured to use stateless OAuth.');
    }

    private function disconnectWarning(ProviderDefinition $provider, bool $lastPrimaryCredential): string
    {
        if ($lastPrimaryCredential) {
            return t('Disconnecting {provider} will leave your account without a password or connected sign-in provider. You may be unable to sign in again.', [
                'provider' => $provider->name,
            ]);
        }

        return t('Are you sure you want to disconnect {provider}?', [
            'provider' => $provider->name,
        ]);
    }
}
