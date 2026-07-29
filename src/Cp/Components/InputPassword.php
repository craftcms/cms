<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;

/**
 * PHP counterpart to the `<craft-input-password>` web component — a text input
 * with a built-in show/hide reveal toggle, the modern replacement for the
 * legacy `Craft.PasswordInput` JS.
 *
 * It's an {@see Input} whose tag and type are fixed to password. The component
 * owns its input type (toggling it between `password` and `text` via the reveal
 * button), so the type is deliberately kept off the host — but the slotted
 * native input still carries `type="password"` for pre-upgrade masking and form
 * posting.
 *
 *     InputPassword::make()
 *         ->name('newPassword')
 *         ->autocomplete('new-password');
 */
class InputPassword extends Input
{
    #[\Override]
    protected string|Closure $type = 'password';

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-input-password';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $attributes = parent::hostAttributes();

        // craft-input-password extends LionInput directly, so it only honors the
        // control properties Lion pushes onto the slotted input on upgrade
        // (placeholder/name/disabled/readonly). Drop `type` — the component owns
        // it (password ⇄ text via the reveal toggle) — and the craft-input-only
        // styling host attributes it doesn't implement. The slotted native input
        // still carries the functional ones (type, maxlength) via
        // Input::inputHtml().
        foreach (['type', 'maxlength', 'size', 'small', 'width', 'center', 'monospace', 'hidden-input'] as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
