<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;

it('renders a button-group host with grouped button children', function () {
    $html = ButtonGroup::make()
        ->id('alignment')
        ->labelledBy('alignment-label')
        ->buttons([
            Button::make()->label('Left'),
            Button::make()->label('Right')->active(),
        ])
        ->toHtml();

    expect($html)->toContainTag('craft-button-group', ['id' => 'alignment', 'role' => 'group', 'aria-labelledby' => 'alignment-label'])
        ->and($html)->toContain('Left')
        ->and($html)->toContain('active="true"')
        ->and(substr_count($html, '</craft-button>'))->toBe(2)
        ->and($html)->not->toContain('craft-listbox');
});

it('emits name and value as host attributes when named, without a hidden input', function () {
    $html = ButtonGroup::make()
        ->id('alignment')
        ->name('alignment')
        ->value('left')
        ->buttons([Button::make()->label('Left')->value('left')])
        ->toHtml();

    expect($html)->toContainTag('craft-button-group', ['id' => 'alignment', 'name' => 'alignment', 'value' => 'left'])
        ->and($html)->not->toContain('type="hidden"');
});

it('emits no name attribute without a name', function () {
    $html = ButtonGroup::make()
        ->buttons([Button::make()->label('One')])
        ->toHtml();

    expect($html)->not->toContain('type="hidden"')
        ->and($html)->not->toContain('name=');
});

it('renders a form-associated multiple button group', function () {
    $html = ButtonGroup::make()
        ->name('topics')
        ->multiple()
        ->buttons([
            Button::make()->label('One')->value('one')->active(),
            Button::make()->label('Two')->value('two'),
        ])
        ->toHtml();

    expect($html)->toContainTag('craft-button-group', ['name' => 'topics', 'multiple' => true, 'role' => 'group'])
        ->and($html)->toContainTag('craft-button', ['value' => 'one', 'active' => 'true']);
});
