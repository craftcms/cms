<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\Enums\Size;

describe('host attributes', function () {
    it('renders defaults without redundant attributes', function () {
        $html = Lightswitch::make()->toHtml();

        expect($html)->toStartWith('<craft-switch>')
            ->and($html)->not->toContain('value=')
            ->and($html)->not->toContain('indeterminate');
    });

    it('renders state and non-default posting values', function () {
        $html = Lightswitch::make()
            ->on()
            ->value('yes')
            ->size(Size::Small)
            ->toHtml();

        expect($html)->toContain(' checked')
            ->and($html)->toContain('value="yes"')
            ->and($html)->toContain('size="small"');
    });

    it('merges extra button attributes over computed defaults', function () {
        $html = Lightswitch::make()
            ->id('ls')
            ->buttonAttributes(['data-test' => '1', 'class' => 'extra', 'role' => 'other-role'])
            ->toHtml();

        expect($html)->toContain('data-test="1"')
            ->and($html)->toContain('extra')
            ->and($html)->toContain('role="other-role"');
    });

    it('only renders on-label when it differs from the label', function () {
        $sameLabels = Lightswitch::make()->label('Enabled')->onLabel('Enabled')->toHtml();
        $distinctLabels = Lightswitch::make()->label('Status')->onLabel('Live')->offLabel('Draft')->toHtml();

        expect($sameLabels)->toContain('label="Enabled"')
            ->and($sameLabels)->not->toContain('on-label')
            ->and($distinctLabels)->toContain('on-label="Live"')
            ->and($distinctLabels)->toContain('off-label="Draft"');
    });

    it('clamps indeterminate to off switches', function () {
        $off = Lightswitch::make()->indeterminate()->toHtml();
        $on = Lightswitch::make()->on()->indeterminate()->toHtml();

        expect($off)->toContain(' indeterminate')
            ->and($on)->not->toContain('indeterminate');
    });
});

describe('switch button', function () {
    it('renders the slotted button with switch semantics', function () {
        $html = Lightswitch::make()->id('my-switch')->on()->toHtml();

        expect($html)->toContain('<craft-switch-button')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('id="my-switch"')
            ->and($html)->toContain('role="switch"')
            ->and($html)->toContain('aria-checked="true"')
            ->and($html)->toContain('size="medium"');
    });

    it('exposes aria-checked=mixed when indeterminate', function () {
        expect(Lightswitch::make()->indeterminate()->toHtml())
            ->toContain('aria-checked="mixed"');
    });

    it('wires toggle targets for Craft.FieldToggle', function () {
        $html = Lightswitch::make()
            ->toggle('#settings')
            ->reverseToggle('other-settings')
            ->toHtml();

        expect($html)->toContain('class="fieldtoggle"')
            ->and($html)->toContain('data-target="#settings"')
            ->and($html)->toContain('data-reverse-target="other-settings"');
    });

    it('wires external label and description associations', function () {
        $html = Lightswitch::make()
            ->labelledBy('a-label')
            ->describedBy('a-description')
            ->toHtml();

        expect($html)->toContain('aria-labelledby="a-label"')
            ->and($html)->toContain('aria-describedby="a-description"');
    });
});

describe('hidden input', function () {
    it('posts value when on, indeterminate value when indeterminate, empty when off', function () {
        $on = Lightswitch::make()->name('enabled')->on()->toHtml();
        $indeterminate = Lightswitch::make()->name('enabled')->indeterminate()->toHtml();
        $off = Lightswitch::make()->name('enabled')->toHtml();

        expect($on)->toContainTag('input', ['type' => 'hidden', 'name' => 'enabled', 'value' => '1', 'slot' => 'hidden-input'])
            ->and($indeterminate)->toContainTag('input', ['type' => 'hidden', 'name' => 'enabled', 'value' => '-', 'slot' => 'hidden-input'])
            ->and($off)->toContainTag('input', ['type' => 'hidden', 'name' => 'enabled', 'value' => '', 'slot' => 'hidden-input']);
    });

    it('renders no hidden input without a name', function () {
        expect(Lightswitch::make()->on()->toHtml())
            ->not->toContain('type="hidden"');
    });
});

it('renders markdown instructions into the help-text slot', function () {
    $html = Lightswitch::make()->instructions('Some **bold** text')->toHtml();

    expect($html)->toContain('slot="help-text"')
        ->and($html)->toContain('<strong>bold</strong>');
});
