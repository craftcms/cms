<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FieldGroup;
use Illuminate\Support\HtmlString;

it('renders component children into the default slot', function () {
    $html = FieldGroup::make()
        ->children([
            Field::make()->label('One'),
            Field::make()->label('Two'),
        ])
        ->toHtml();

    expect($html)->toStartWith('<craft-field-group')
        ->and($html)->toContain('label="One"')
        ->and($html)->toContain('label="Two"')
        ->and(substr_count($html, '<craft-field '))->toBe(2)
        ->and($html)->not->toContain('slot=');
});

it('mixes component, Htmlable, and encoded string children', function () {
    $html = FieldGroup::make()
        ->children([
            Field::make()->label('One'),
            new HtmlString('<hr>'),
            'plain & text',
        ])
        ->toHtml();

    expect($html)->toContain('<hr>')
        ->and($html)->toContain('plain &amp; text');
});

it('sets the gap custom property', function () {
    expect(FieldGroup::make()->gap('var(--c-spacing-xl)')->toHtml())
        ->toContain('style="--gap: var(--c-spacing-xl);"');

    expect(FieldGroup::make()->toHtml())->not->toContain('style=');
});
