<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Callout;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Enums\Variant;
use Illuminate\Support\HtmlString;

it('renders attributes', function () {
    $html = Callout::make()
        ->variant('warning')
        ->appearance('plain')
        ->title('Heads up')
        ->icon('lightbulb')
        ->rounded('none')
        ->inline()
        ->toHtml();

    expect($html)->toStartWith('<craft-callout')
        ->and($html)->toContain('variant="warning"')
        ->and($html)->toContain('appearance="plain"')
        ->and($html)->toContain('title="Heads up"')
        ->and($html)->toContain('icon="lightbulb"')
        ->and($html)->toContain('rounded="none"')
        ->and($html)->toContain(' inline');
});

it('omits unset attributes so the web component defaults apply', function () {
    $html = Callout::make()->content('Hi')->toHtml();

    expect($html)->not->toContain('variant=')
        ->and($html)->not->toContain('icon=')
        ->and($html)->not->toContain('inline');
});

it('encodes plain string content and trusts Htmlable content', function () {
    expect(Callout::make()->content('a <b> c')->toHtml())
        ->toContain('a &lt;b&gt; c');

    expect(Callout::make()->content(new HtmlString('<em>raw</em>'))->toHtml())
        ->toContain('<em>raw</em>');
});

it('renders a nested component as content', function () {
    $html = Callout::make()
        ->content(Field::make()->label('Inner'))
        ->toHtml();

    expect($html)->toContain('<craft-field label="Inner">');
});

it('accepts enum variants and appearances', function () {
    $html = Callout::make()
        ->variant(Variant::Danger)
        ->appearance(Appearance::OutlineFill)
        ->toHtml();

    expect($html)->toContain('variant="danger"')
        ->and($html)->toContain('appearance="outline-fill"');
});

it('rejects invalid variant and appearance strings', function () {
    expect(fn () => Callout::make()->variant('sparkly')->toHtml())
        ->toThrow(ValueError::class);

    expect(fn () => Callout::make()->appearance('sparkly')->toHtml())
        ->toThrow(ValueError::class);
});

it('renders the size and padding settings', function () {
    $html = Callout::make()->size('small')->padding('lg')->toHtml();

    expect($html)->toContain('size="small"')
        ->and($html)->toContain('padding="lg"');
});

it('passes padding values through without validating them', function (string|int $padding, string $expected) {
    expect(Callout::make()->padding($padding)->toHtml())->toContain($expected);
})->with([
    ['md', 'padding="md"'],
    [0, 'padding="0"'],
    [12, 'padding="12"'],
    ['1.5rem', 'padding="1.5rem"'],
]);

it('omits size and padding when unset, so the web component defaults apply', function () {
    $html = Callout::make()->content('Hi')->toHtml();

    expect($html)->not->toContain('size=')
        ->and($html)->not->toContain('padding=');
});
