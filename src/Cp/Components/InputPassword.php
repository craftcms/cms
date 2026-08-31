<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

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
    protected string $type = 'password';

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-input-password';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $attributes = parent::hostAttributes();

        // Drop `type`: the component owns it, toggling password ⇄ text via the
        // reveal button. The slotted native input still carries it via
        // Input::inputHtml() for pre-upgrade masking and form posting.
        unset($attributes['type']);

        return $attributes;
    }
}
