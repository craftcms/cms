<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\InputPassword;
use CraftCms\Cms\Cp\FormFields;
use Illuminate\Validation\Rules\Password;

describe('input-password', function () {
    it('renders a craft-input-password with a slotted password input', function () {
        $html = InputPassword::make()
            ->id('new-password')
            ->name('newPassword')
            ->toHtml();

        expect($html)->toContain('<craft-input-password')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('type="password"')
            ->and($html)->toContain('id="new-password"')
            ->and($html)->toContain('name="newPassword"');
    });

    it('keeps the type off the host but on the native input', function () {
        $html = InputPassword::make()->id('p')->toHtml();

        // The component owns the type (password ⇄ text via the reveal toggle),
        // so it must not be reflected on the host element.
        expect($html)->not->toContain('<craft-input-password type=')
            ->and($html)->not->toContain('<craft-input-password type="password"')
            // …but the slotted native input still carries it for pre-upgrade
            // masking and form posting.
            ->and($html)->toContain('type="password"');
    });

    it('carries the host name for Lion upgrade, like craft-input', function () {
        $html = InputPassword::make()->id('p')->name('newPassword')->toHtml();

        expect($html)->toContain('<craft-input-password name="newPassword">');
    });

    it('renders disabled and readonly state', function () {
        $html = InputPassword::make()->id('p')->disabled()->readOnly()->toHtml();

        expect($html)->toContain(' disabled')
            ->and($html)->toContain(' readonly');
    });
});

describe('FormFields::passwordFromConfig', function () {
    it('maps the legacy password config onto the component', function () {
        $html = FormFields::passwordFromConfig([
            'id' => 'newPassword',
            'name' => 'newPassword',
            'autocomplete' => 'new-password',
        ])->toHtml();

        expect($html)->toContain('<craft-input-password')
            ->and($html)->toContain('id="newPassword"')
            ->and($html)->toContain('name="newPassword"')
            ->and($html)->toContain('autocomplete="new-password"')
            ->and($html)->toContain('passwordrules="'.Password::defaults()->toPasswordRulesString().'"')
            // Preserves the legacy `.password` input class for CSS/JS keyed on it.
            ->and($html)->toContain('password');
    });

    it('forces the password type regardless of a configured type', function () {
        $html = FormFields::passwordFromConfig(['id' => 'p', 'type' => 'text'])->toHtml();

        expect($html)->toContain('type="password"')
            ->and($html)->not->toContain('type="text"');
    });
});
