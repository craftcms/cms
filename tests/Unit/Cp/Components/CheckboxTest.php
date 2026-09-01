<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Checkbox;
use Illuminate\Support\HtmlString;
use Twig\Markup;

describe('input', function () {
    it('renders the slotted native input inside the web component', function () {
        $html = Checkbox::make()
            ->id('cb')
            ->name('remember')
            ->checked()
            ->toHtml();

        expect($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'remember', 'value' => ''])
            ->and($html)->toContain('<craft-checkbox>')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('type="checkbox"')
            ->and($html)->toContain('id="cb"')
            ->and($html)->toContain('name="remember"')
            ->and($html)->toContain('value="1"')
            ->and($html)->toContain(' checked')
            ->and($html)->toContain('class="checkbox"');
    });

    it('lets inputAttributes override computed defaults like type', function () {
        $html = Checkbox::make()
            ->id('cb')
            ->name('n')
            ->checked()
            ->inputAttributes(['type' => 'other-type'])
            ->toHtml();

        // Only the type changed; the default class and state wiring survive.
        expect($html)->toContain('type="other-type"')
            ->and($html)->toContain('class="checkbox"')
            ->and($html)->toContain('name="n"')
            ->and($html)->toContain(' checked')
            ->and($html)->toContain('slot="input"');
    });

    it('stringifies numeric values', function () {
        $html = Checkbox::make()->id('cb')->value(1.78)->toHtml();

        expect($html)->toContain('value="1.78"');
    });

    it('nests the always-post hidden input in the host, ahead of the checkbox', function () {
        $html = Checkbox::make()->id('cb')->name('remember')->toHtml();

        // The control has to be a single root element, or it can't be slotted
        // into a <craft-field> — the slot attribute would land on the hidden
        // input and the host would never render.
        expect($html)->toStartWith('<craft-checkbox>')
            ->and($html)->toEndWith('</craft-checkbox>')
            ->and(strpos($html, 'type="hidden"'))->toBeLessThan(strpos($html, 'type="checkbox"'));
    });

    it('skips the always-post hidden input for array names', function () {
        expect(Checkbox::make()->name('options[]')->toHtml())
            ->not->toContain('type="hidden"');
    });

    it('wires toggle targets with the fieldtoggle class', function () {
        $html = Checkbox::make()->id('cb')->toggle('#settings')->toHtml();

        expect($html)->toContain('fieldtoggle')
            ->and($html)->toContain('data-target="#settings"');
    });

    it('merges extra input attributes', function () {
        $html = Checkbox::make()
            ->id('cb')
            ->inputAttributes(['class' => 'extra', 'data-foo' => '1'])
            ->toHtml();

        expect($html)->toContain('class="checkbox extra"')
            ->and($html)->toContain('data-foo="1"');
    });
});

describe('label', function () {
    it('renders the associated label with encoded text', function () {
        $html = Checkbox::make()->id('cb')->label('Tom & Jerry')->toHtml();

        expect($html)->toContainTag('label', ['slot' => 'label', 'for' => 'cb', 'id' => 'cb-label'])
            ->and($html)->toContain('Tom &amp; Jerry');
    });

    it('renders a Twig Markup label unencoded', function () {
        $html = Checkbox::make()->id('cb')->label(new Markup('<b>All</b>', 'UTF-8'))->toHtml();

        expect($html)->toContainTag('label', ['slot' => 'label', 'for' => 'cb', 'id' => 'cb-label'])
            ->and($html)->toContain('<b>All</b>');
    });

    it('renders no label element without label content', function () {
        expect(Checkbox::make()->id('cb')->toHtml())->not->toContain('<label');
    });

    it('renders an icon chip with a color tint', function () {
        $html = Checkbox::make()->id('cb')->label('Red')->icon('circle')->color('red')->toHtml();

        expect($html)->toContain('cp-icon puny')
            ->and($html)->toContain('--icon-color: red;')
            ->and($html)->toContain('<span>Red</span>');
    });

    it('renders a color swatch without an icon', function () {
        $html = Checkbox::make()->id('cb')->label('Red')->color('red')->toHtml();

        expect($html)->toContain('color-preview')
            ->and($html)->toContain('background-color: red');
    });

    it('renders markdown info beside the label', function () {
        $html = Checkbox::make()->id('cb')->label('Opt')->info('Some *info*')->toHtml();

        expect($html)->toContain('<craft-info-icon>')
            ->and($html)->toContain('<em>info</em>');
    });
});

describe('custom-option mode', function () {
    it('renders the Custom label and the custom input wrapper', function () {
        $html = Checkbox::make()
            ->id('cb')
            ->label('Ignored')
            ->custom(new HtmlString('<input type="text" class="custom-option-input">'))
            ->toHtml();

        expect($html)->toContain('>Custom:</label>')
            ->and($html)->not->toContain('Ignored')
            ->and($html)->toContainTag('div', ['class' => 'custom-option-wrapper'])
            ->and($html)->toContainTag('input', ['type' => 'text', 'class' => 'custom-option-input']);
    });
});
