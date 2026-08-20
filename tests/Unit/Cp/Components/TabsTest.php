<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Enums\TabsLayout;
use CraftCms\Cms\Cp\Enums\TabsPlacement;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\ui;

it('renders attributes', function () {
    $html = Tabs::make()
        ->layout('vertical')
        ->selectedIndex(1)
        ->toHtml();

    expect($html)->toStartWith('<craft-tabs')
        ->and($html)->toContain('layout="vertical"')
        ->and($html)->toContain('selected-index="1"');
});

it('omits unset attributes so the web component defaults apply', function () {
    $html = Tabs::make()->tab('One', 'Panel one')->toHtml();

    expect($html)->not->toContain('layout=')
        ->and($html)->not->toContain('selected-index=');
});

it('accepts an enum layout', function () {
    expect(Tabs::make()->layout(TabsLayout::Vertical)->toHtml())
        ->toContain('layout="vertical"');
});

it('rejects an invalid layout string', function () {
    expect(fn () => Tabs::make()->layout('diagonal')->toHtml())
        ->toThrow(ValueError::class);
});

it('emits each tab immediately followed by its panel', function () {
    $html = Tabs::make()
        ->tab('One', new HtmlString('<p>Panel one</p>'))
        ->tab('Two', new HtmlString('<p>Panel two</p>'))
        ->toHtml();

    // Order is the pairing: the web component matches the nth tab to the nth
    // panel, so an interleaving regression would silently mismatch content.
    expect($html)->toContain(
        '<craft-tab slot="tab">One</craft-tab>'.
        '<div slot="panel"><p>Panel one</p></div>'.
        '<craft-tab slot="tab">Two</craft-tab>'.
        '<div slot="panel"><p>Panel two</p></div>',
    );
});

it('encodes plain-string panels and labels', function () {
    $html = Tabs::make()->tab('A & B', '<script>alert(1)</script>')->toHtml();

    expect($html)->toContain('A &amp; B')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('<script>');
});

it('takes prepared tabs, keeping their own panels', function () {
    $html = Tabs::make()
        ->tabs([
            Tab::make()->label('One')->panel('Panel one'),
            Tab::make()->label('Two')->panel('Panel two')->disabled(),
        ])
        ->toHtml();

    expect($html)->toContain('<craft-tab slot="tab">One</craft-tab>')
        ->and($html)->toContain('<craft-tab slot="tab" disabled>Two</craft-tab>')
        ->and($html)->toContain('Panel one')
        ->and($html)->toContain('Panel two');
});

it('takes tabs as config arrays', function () {
    $html = Tabs::make()
        ->tabs([
            ['label' => 'One', 'panel' => 'Panel one'],
            ['label' => 'Two', 'panel' => 'Panel two', 'disabled' => true],
        ])
        ->toHtml();

    expect($html)->toContain('<craft-tab slot="tab">One</craft-tab>')
        ->and($html)->toContain('disabled')
        ->and($html)->toContain('Panel two');
});

it('rejects tab items that are neither a Tab nor a config array', function () {
    expect(fn () => Tabs::make()->tabs(['One']))
        ->toThrow(InvalidArgumentException::class);
});

it('replaces the tabs when tabs() is called', function () {
    $html = Tabs::make()
        ->tab('Stale', 'Stale panel')
        ->tabs([['label' => 'Fresh', 'panel' => 'Fresh panel']])
        ->toHtml();

    expect($html)->not->toContain('Stale')
        ->and($html)->toContain('Fresh');
});

it('renders tabs that point at external panels', function () {
    // External-panel mode: the tabs name panels rendered elsewhere, so the
    // component emits no panels of its own.
    $html = Tabs::make()
        ->tabs([
            ['label' => 'Content', 'controls' => 'form-tab-1'],
            ['label' => 'Settings', 'controls' => 'form-tab-2'],
        ])
        ->toHtml();

    expect($html)->toContain('<craft-tab slot="tab" controls="form-tab-1">Content</craft-tab>')
        ->and($html)->toContain('<craft-tab slot="tab" controls="form-tab-2">Settings</craft-tab>')
        ->and($html)->not->toContain('slot="panel"');
});

it('keeps a tab in the tab slot', function () {
    // The pairing depends on the slot, so this isn't reassignable the way
    // ViewComponent::slot() normally allows.
    expect(Tab::make()->label('One')->slot('panel')->toHtml())
        ->toContain('slot="tab"')
        ->and(Tab::make()->label('One')->slot('panel')->toHtml())
        ->not->toContain('slot="panel"');
});

it('builds from the registry', function () {
    $html = (string) ui('tabs', [
        'layout' => 'vertical',
        'tabs' => [
            ['label' => 'One', 'panel' => 'Panel one'],
        ],
    ]);

    expect($html)->toStartWith('<craft-tabs')
        ->and($html)->toContain('layout="vertical"')
        ->and($html)->toContain('<craft-tab slot="tab">One</craft-tab>');
});

it('renders the size, placement, and collapsible settings', function () {
    $html = Tabs::make()
        ->size('small')
        ->placement(TabsPlacement::InlineStart)
        ->collapsible()
        ->tab('One', 'Panel one')
        ->toHtml();

    expect($html)->toContain('size="small"')
        ->and($html)->toContain('placement="inline-start"')
        ->and($html)->toContain('collapsible');
});

it('validates placement strings against the enum', function () {
    expect(Tabs::make()->placement('inline-end')->toHtml())
        ->toContain('placement="inline-end"');

    expect(fn () => Tabs::make()->placement('sideways')->toHtml())
        ->toThrow(ValueError::class);
});

it('omits the new settings when unset, so the web component defaults apply', function () {
    $html = Tabs::make()->tab('One', 'Panel one')->toHtml();

    expect($html)->not->toContain('size=')
        ->and($html)->not->toContain('placement=')
        ->and($html)->not->toContain('collapsible');
});

it('builds placement and collapsible from the registry', function () {
    $html = (string) ui('tabs', [
        'placement' => 'inline-start',
        'collapsible' => true,
        'size' => 'small',
        'tabs' => [
            ['label' => 'One', 'panel' => 'Panel one'],
        ],
    ]);

    expect($html)->toContain('placement="inline-start"')
        ->and($html)->toContain('collapsible')
        ->and($html)->toContain('size="small"');
});
