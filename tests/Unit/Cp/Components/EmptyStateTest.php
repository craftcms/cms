<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\EmptyState;
use CraftCms\Cms\Cp\Components\Icon;
use CraftCms\Cms\Cp\Enums\Appearance;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\ui;

it('renders attributes', function () {
    $html = EmptyState::make()
        ->label('No results')
        ->icon('empty-set')
        ->appearance('outline')
        ->toHtml();

    expect($html)->toStartWith('<craft-empty')
        ->and($html)->toContain('label="No results"')
        ->and($html)->toContain('icon="empty-set"')
        ->and($html)->toContain('appearance="outline"');
});

it('omits unset attributes so the web component defaults apply', function () {
    expect(EmptyState::make()->toHtml())->toBe('<craft-empty></craft-empty>');
});

it('accepts an appearance enum', function () {
    expect(EmptyState::make()->appearance(Appearance::Plain)->toHtml())
        ->toContain('appearance="plain"');
});

it('rejects appearances the web component does not support', function () {
    expect(fn () => EmptyState::make()->appearance('solid'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => EmptyState::make()->appearance('sparkly'))
        ->toThrow(ValueError::class);
});

it('renders actions into the default slot', function () {
    $html = EmptyState::make()
        ->label('No entries yet')
        ->actions(Button::make()->label('New entry'))
        ->toHtml();

    expect($html)->toContain('<craft-button')
        ->and($html)->not->toContain('slot=');
});

it('renders the graphic and message slots', function () {
    $html = EmptyState::make()
        ->graphic(Icon::make()->name('folder'))
        ->message(new HtmlString('<p>Nothing <em>here</em></p>'))
        ->toHtml();

    expect($html)->toContain('slot="graphic"')
        ->and($html)->toContain('<p slot="content">Nothing <em>here</em></p>');
});

it('encodes plain string content', function () {
    expect(EmptyState::make()->message('a <b> c')->toHtml())
        ->toContain('a &lt;b&gt; c');
});

it('is available through the ui() helper', function () {
    expect((string) ui('empty', ['label' => 'No results', 'appearance' => 'outline']))
        ->toContain('<craft-empty')
        ->toContain('appearance="outline"');
});
