<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\EditUserScreensResolving;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * The screens an account is edited through — Profile, Permissions, Preferences,
 * Addresses, and the account-security ones — and the navigation between them.
 *
 * Both the legacy `CpScreenResponse` screens and the Inertia profile screen
 * need the same list, so it lives here rather than in the controller trait that
 * used to own it.
 */
#[Singleton]
class EditUserScreens
{
    public const string PROFILE = 'profile';

    public const string ADDRESSES = 'addresses';

    public const string PERMISSIONS = 'permissions';

    public const string PREFERENCES = 'preferences';

    public const string PASSWORD = 'password';

    public const string PASSKEYS = 'passkeys';

    public const string SIGN_IN_PROVIDERS = 'sign-in-providers';

    /**
     * The screens available for this user, keyed by screen name.
     *
     * @return array<string, array{label: string, url?: string}>
     */
    public function screens(User $user): array
    {
        $screens = [
            self::PROFILE => ['label' => t('Profile')],
        ];

        if ($this->showPermissionsScreen()) {
            $screens[self::PERMISSIONS] = ['label' => t('Permissions')];
        }

        if ($user->getIsCurrent()) {
            $screens[self::PREFERENCES] = ['label' => t('Preferences')];
        }

        $screens[self::ADDRESSES] = ['label' => t('Addresses')];

        event($event = new EditUserScreensResolving(currentUserElement(), $user, $screens));

        $screens = $event->screens;

        if ($user->getIsCurrent() && $user->getHasPassword()) {
            $screens[self::PASSWORD] = ['label' => t('Password & Verification')];
            $screens[self::PASSKEYS] = ['label' => t('Passkeys')];
        }

        if ($this->showSignInProvidersScreen($user)) {
            $screens[self::SIGN_IN_PROVIDERS] = ['label' => t('Sign-in Providers')];
        }

        return $screens;
    }

    /**
     * The account navigation, with the account-security screens nested under
     * their own heading.
     *
     * Both levels are lists, not keyed by screen name: the CP shell types this
     * as `NavItem[]` and decides whether to draw the secondary nav from the
     * item count, which a JSON object wouldn't have.
     *
     * @param  array<string, array{label: string, url?: string}>|null  $screens
     * @return list<NavItem>
     */
    public function subnav(User $user, string $screen, ?array $screens = null): array
    {
        $screens ??= $this->screens($user);
        $items = [];
        $securityItem = null;
        $current = &$items;

        foreach ($screens as $name => $info) {
            if (
                $securityItem === null &&
                in_array($name, [self::PASSWORD, self::SIGN_IN_PROVIDERS], true)
            ) {
                $securityItem = new NavItem([
                    'label' => t('Account Security'),
                    'url' => '#',
                    'selected' => false,
                    'group' => true,
                    'subnav' => [],
                ]);
                $items[] = $securityItem;
                $current = &$securityItem->subnav;
            }

            $selected = $name === $screen;

            if ($selected && $securityItem) {
                $securityItem->selected = true;
            }

            $current[] = new NavItem([
                'label' => $info['label'],
                'url' => $info['url'] ?? $this->url($user, $name),
                'selected' => $selected,
            ]);
        }

        return $items;
    }

    /**
     * The same navigation as a nested list for the legacy `_includes/nav`
     * template, which takes plain arrays rather than {@see NavItem}s.
     *
     * @param  array<string, array{label: string, url?: string}>|null  $screens
     * @return list<array<string, mixed>>
     */
    public function sidebarItems(User $user, string $screen, ?array $screens = null): array
    {
        $screens ??= $this->screens($user);
        $items = [];
        $nested = null;
        $current = &$items;

        foreach ($screens as $name => $info) {
            if (
                $nested === null &&
                in_array($name, [self::PASSWORD, self::SIGN_IN_PROVIDERS], true)
            ) {
                $items[] = ['heading' => t('Account Security'), 'nested' => []];
                $nested = array_key_last($items);
                $current = &$items[$nested]['nested'];
            }

            $current[] = [
                'label' => $info['label'],
                'url' => $info['url'] ?? $this->url($user, $name),
                'selected' => $name === $screen,
            ];
        }

        return $items;
    }

    public function url(User $user, string $screen): string
    {
        $basePath = $user->getIsCurrent() ? 'myaccount' : "users/$user->id";

        return Url::cpUrl($screen === self::PROFILE ? $basePath : "$basePath/$screen");
    }

    public function showPermissionsScreen(): bool
    {
        return (bool) currentUser()?->can('viewPermissionsScreen', User::class);
    }

    public function showSignInProvidersScreen(User $user): bool
    {
        return $user->getIsCurrent() && app(OAuth::class)->getProviderDefinitions()->isNotEmpty();
    }
}
