<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxGroup;

describe('checkbox group', function () {
    it('renders the container, hidden input, and wrapped checkboxes', function () {
        $html = CheckboxGroup::make()
            ->id('colors')
            ->name('colors')
            ->options([
                Checkbox::make()->id('red')->label('Red')->name('colors[]')->value('red'),
                Checkbox::make()->id('blue')->label('Blue')->name('colors[]')->value('blue'),
            ])
            ->toHtml();

        expect($html)->toContain('<craft-checkbox-group id="colors" class="checkbox-group">')
            ->and($html)->toContain('<input type="hidden" name="colors" value>')
            ->and($html)->toContain('value="red"')
            ->and($html)->toContain('value="blue"')
            ->and($html)->not->toContain('data-custom');
    });

    it('skips the hidden input without a name', function () {
        expect(CheckboxGroup::make()->id('g')->toHtml())
            ->not->toContain('type="hidden"');
    });

    it('marks custom-option checkboxes on their wrappers', function () {
        $html = CheckboxGroup::make()
            ->id('g')
            ->options([
                Checkbox::make()->id('c1')->name('opts[]')->custom('<input class="text">')->checked(),
            ])
            ->toHtml();

        expect($html)->toContain('data-custom');
    });

    it('renders the add-option button when a custom option template is set', function () {
        $html = CheckboxGroup::make()
            ->id('g')
            ->name('opts')
            ->customOptionTemplate('<div data-custom><input id="__ID__"></div>')
            ->toHtml();

        expect($html)->toContain('id="g-add-btn"')
            ->and($html)->toContain('Add option');
    });

    it('omits the add-option button without a template', function () {
        expect(CheckboxGroup::make()->id('g')->name('opts')->toHtml())
            ->not->toContain('add-btn');
    });
});
