<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\InputColor;
use CraftCms\Cms\Cp\FormFields;

describe('input-color', function () {
    it('renders a craft-input-color with a slotted hex input', function () {
        $html = InputColor::make()
            ->id('fill')
            ->name('fill')
            ->value('7ab55c')
            ->toHtml();

        expect($html)->toContain('<craft-input-color')
            ->and($html)->toContain('slot="input"')
            ->and($html)->toContain('id="fill"')
            ->and($html)->toContain('name="fill"')
            ->and($html)->toContain('value="7ab55c"');
    });

    it('keeps only the Lion control props on the host', function () {
        $html = InputColor::make()->id('c')->name('c')->toHtml();

        // The component owns its type/layout; craft-input-only styling host
        // attributes must not appear on the element.
        expect($html)->toContain('<craft-input-color name="c">')
            ->and($html)->not->toContain('maxlength')
            ->and($html)->not->toContain('<craft-input-color type=');
    });

    it('emits presets as a JSON array attribute', function () {
        $html = InputColor::make()->id('c')->presets(['#ffffff', '#000000'])->toHtml();

        expect($html)->toContain('presets=')
            ->and($html)->toContain('ffffff')
            ->and($html)->toContain('000000');
    });

    it('omits presets when empty', function () {
        $html = InputColor::make()->id('c')->toHtml();

        expect($html)->not->toContain('presets=');
    });
});

describe('FormFields::colorFromConfig', function () {
    it('strips a leading # from the value and keeps the .color-input class', function () {
        $html = FormFields::colorFromConfig([
            'id' => 'bg',
            'name' => 'bg',
            'value' => '#7ab55c',
        ])->toHtml();

        expect($html)->toContain('<craft-input-color')
            ->and($html)->toContain('value="7ab55c"')
            ->and($html)->not->toContain('value="#7ab55c"')
            ->and($html)->toContain('color-input');
    });

    it('passes presets through to the component', function () {
        $html = FormFields::colorFromConfig([
            'id' => 'bg',
            'presets' => ['#ffffff', '#7ab55c'],
        ])->toHtml();

        expect($html)->toContain('presets=')
            ->and($html)->toContain('7ab55c');
    });
});
