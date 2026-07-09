<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;

it('wraps the group in a listbox with grouped button children', function () {
    $html = ButtonGroup::make()
        ->id('alignment')
        ->labelledBy('alignment-label')
        ->buttons([
            Button::make()->label('Left'),
            Button::make()->label('Right')->active(),
        ])
        ->toHtml();

    expect($html)->toStartWith('<craft-listbox>')
        ->and($html)->toContain('<craft-button-group id="alignment" role="group" aria-labelledby="alignment-label">')
        ->and($html)->toContain('Left')
        ->and($html)->toContain('active="true"')
        ->and(substr_count($html, '<craft-button'))->toBe(3);
});

it('posts the selected value through a hidden input when named', function () {
    $html = ButtonGroup::make()
        ->id('alignment')
        ->name('alignment')
        ->value('left')
        ->buttons([Button::make()->label('Left')])
        ->toHtml();

    expect($html)->toContain('type="hidden" name="alignment" value="left"')
        ->and($html)->toContain('id="alignment-input"');
});

it('renders no hidden input without a name', function () {
    expect(ButtonGroup::make()->buttons([Button::make()->label('One')])->toHtml())
        ->not->toContain('type="hidden"');
});

it('disables the listbox', function () {
    expect(ButtonGroup::make()->disabled()->toHtml())
        ->toStartWith('<craft-listbox disabled>');
});
