<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\template;

class UserPasswordViewModel extends ViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function userId(): int
    {
        return $this->user->id;
    }

    /**
     * The Two-Step Verification method listing, rendered as an HTML fragment so
     * the existing `Craft.AuthMethodSetup` module can boot on it client-side.
     */
    public function authMethods(): HtmlFragment
    {
        return HtmlStack::capture(fn (): string => template('users/_auth-methods', templateMode: TemplateMode::Cp));
    }
}
