<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Validation\Rules\Password;

class UserPasswordViewModel extends ViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly AuthMethods $auth,
    ) {}

    public function userId(): int
    {
        return $this->user->id;
    }

    public function passwordRules(): string
    {
        return Password::defaults()->toPasswordRulesString();
    }

    /**
     * The Two-Step Verification methods available to the user, as structured
     * data for the native Vue listing.
     *
     * @return list<array{
     *     type: class-string<AuthMethodInterface>,
     *     handle: string,
     *     name: string,
     *     description: string,
     *     isActive: bool,
     *     actions: list<array{label: string, icon: string|null, action: string|null, requireElevatedSession: bool, download: bool}>,
     * }>
     */
    public function authMethods(): array
    {
        return $this->auth->getAvailableMethods($this->user)
            ->map(fn (AuthMethodInterface $method): array => [
                'type' => $method::class,
                'handle' => $method::handle(),
                'name' => $method::displayName(),
                'description' => $method::description(),
                'isActive' => $method->isActive(),
                'actions' => $this->methodActions($method),
            ])
            ->values()
            ->all();
    }

    /**
     * Maps an active method's action-menu items onto structured data. The
     * "Remove" item is added client-side and isn't included here.
     *
     * @return list<array{label: string, icon: string|null, action: string|null, requireElevatedSession: bool, download: bool}>
     */
    private function methodActions(AuthMethodInterface $method): array
    {
        if (! $method->isActive()) {
            return [];
        }

        return collect($method->getActionMenuItems())
            ->map(fn (array $item): array => [
                'label' => $item['label'] ?? '',
                'icon' => $item['icon'] ?? null,
                'action' => $item['action'] ?? null,
                'requireElevatedSession' => (bool) ($item['requireElevatedSession'] ?? false),
                'download' => isset($item['action']) && str_contains((string) $item['action'], 'download'),
            ])
            ->values()
            ->all();
    }
}
