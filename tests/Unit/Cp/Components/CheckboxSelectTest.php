<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxSelect;

describe('checkbox select', function () {
    it('renders the fieldset with wrapped items', function () {
        $html = CheckboxSelect::make()
            ->id('sources')
            ->name('sources')
            ->options([
                Checkbox::make()->id('a')->name('sources[]')->value('a'),
            ])
            ->toHtml();

        expect($html)->toContain('<fieldset id="sources" class="cp-checkbox-select">')
            ->and($html)->toContain('<input type="hidden" name="sources" value>')
            ->and($html)->toContain('<div class="cp-checkbox-select__item">');
    });

    it('renders the All checkbox instead of its own hidden input', function () {
        $html = CheckboxSelect::make()
            ->id('sources')
            ->name('sources')
            ->allCheckbox(Checkbox::make()->id('all')->name('sources')->value('*'))
            ->toHtml();

        // The only hidden input is the All checkbox's own always-post input.
        expect($html)->toContain('value="*"')
            ->and(substr_count($html, 'type="hidden"'))->toBe(1);
    });

    it('skips the hidden input for array names', function () {
        expect(CheckboxSelect::make()->id('s')->name('sources[]')->toHtml())
            ->not->toContain('type="hidden"');
    });

    it('renders the storage key as a data attribute', function () {
        expect(CheckboxSelect::make()->id('s')->storageKey('my-key')->toHtml())
            ->toContain('data-storage-key="my-key"');
    });

    it('wraps in the sortable web component when sortable', function () {
        $html = CheckboxSelect::make()->id('s')->sortable()->toHtml();
        $disabledHtml = CheckboxSelect::make()->id('s')->sortable()->disabled()->toHtml();

        expect($html)->toStartWith('<craft-sortable-checkbox-select>')
            ->and($html)->toEndWith('</craft-sortable-checkbox-select>')
            ->and($disabledHtml)->toContain('<craft-sortable-checkbox-select disabled>');
    });

    it('does not wrap when not sortable', function () {
        expect(CheckboxSelect::make()->id('s')->toHtml())
            ->not->toContain('craft-sortable-checkbox-select');
    });
});
