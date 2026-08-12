<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\Pane;
use CraftCms\Cms\Cp\Enums\PaneAppearance;
use CraftCms\Cms\Cp\Enums\PaneVariant;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\ui;

it('renders attributes', function () {
    $html = Pane::make()
        ->appearance('sunken')
        ->variant('code')
        ->padding('md')
        ->label('Details')
        ->toHtml();

    expect($html)->toStartWith('<craft-pane')
        ->and($html)->toContain('appearance="sunken"')
        ->and($html)->toContain('variant="code"')
        ->and($html)->toContain('padding="md"')
        ->and($html)->toContain('label="Details"');
});

it('omits unset attributes so the web component defaults apply', function () {
    $html = Pane::make()->content('Hi')->toHtml();

    expect($html)->not->toContain('appearance=')
        ->and($html)->not->toContain('variant=')
        ->and($html)->not->toContain('padding=')
        ->and($html)->not->toContain('label=');
});

it('accepts enum appearances and variants', function () {
    $html = Pane::make()
        ->appearance(PaneAppearance::Outline)
        ->variant(PaneVariant::Error)
        ->toHtml();

    expect($html)->toContain('appearance="outline"')
        ->and($html)->toContain('variant="error"');
});

it('rejects invalid appearance and variant strings', function () {
    expect(fn () => Pane::make()->appearance('sparkly')->toHtml())
        ->toThrow(ValueError::class);

    expect(fn () => Pane::make()->variant('sparkly')->toHtml())
        ->toThrow(ValueError::class);
});

it('rejects the shared appearances that a pane has no surface for', function () {
    expect(fn () => Pane::make()->appearance('solid')->toHtml())
        ->toThrow(ValueError::class);
});

it('passes padding values through without validating them', function (string|int $padding, string $expected) {
    expect(Pane::make()->padding($padding)->toHtml())->toContain($expected);
})->with([
    'scale keyword' => ['sm', 'padding="sm"'],
    'zero' => [0, 'padding="0"'],
    'zero string' => ['0', 'padding="0"'],
    'unitless number' => [24, 'padding="24"'],
    'css length' => ['1.5rem', 'padding="1.5rem"'],
    'css function' => ['var(--c-spacing-xl)', 'padding="var(--c-spacing-xl)"'],
]);

it('encodes plain string content and trusts Htmlable content', function () {
    expect(Pane::make()->content('a <b> c')->toHtml())
        ->toContain('a &lt;b&gt; c');

    expect(Pane::make()->content(new HtmlString('<em>raw</em>'))->toHtml())
        ->toContain('<em>raw</em>');
});

it('renders a nested component in the default slot', function () {
    $html = Pane::make()
        ->content(Button::make()->label('Save'))
        ->toHtml();

    expect($html)->toContain('<craft-button')
        ->and($html)->not->toContain('slot=');
});

it('assigns named slots', function (string $method, string $slot) {
    $html = Pane::make()->{$method}(new HtmlString('<div>Slotted</div>'))->toHtml();

    expect($html)->toContain(sprintf('<div slot="%s">Slotted</div>', $slot));
})->with([
    'header' => ['header', 'header'],
    'title' => ['title', 'title'],
    'header actions' => ['headerActions', 'header-actions'],
    'body' => ['body', 'body'],
    'footer' => ['footer', 'footer'],
    'footer content' => ['footerContent', 'footer-content'],
    'feedback' => ['feedback', 'feedback'],
    'actions' => ['actions', 'actions'],
    'primary action' => ['primaryAction', 'primary-action'],
    'secondary action' => ['secondaryAction', 'secondary-action'],
]);

it('wraps plain string slot content in a span', function () {
    expect(Pane::make()->title('Details')->toHtml())
        ->toContain('<span slot="title">Details</span>');
});

it('places a nested component directly in a named slot', function () {
    $html = Pane::make()
        ->primaryAction(Button::make()->label('Save'))
        ->secondaryAction(Button::make()->label('Cancel'))
        ->toHtml();

    expect($html)->toContain('slot="primary-action"')
        ->and($html)->toContain('slot="secondary-action"');
});

it('keeps the label attribute and the title slot separate', function () {
    $html = Pane::make()
        ->label('Attribute')
        ->title(new HtmlString('<h2>Slot</h2>'))
        ->toHtml();

    expect($html)->toContain('label="Attribute"')
        ->and($html)->toContain('<h2 slot="title">Slot</h2>');
});

it('is configurable from a config array', function () {
    $html = ui('pane', [
        'appearance' => 'outline',
        'padding' => 'sm',
        'label' => 'From config',
        'content' => 'Body',
    ])->toHtml();

    expect($html)->toStartWith('<craft-pane')
        ->and($html)->toContain('appearance="outline"')
        ->and($html)->toContain('padding="sm"')
        ->and($html)->toContain('label="From config"');
});
