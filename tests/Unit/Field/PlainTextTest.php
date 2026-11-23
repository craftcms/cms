<?php

declare(strict_types=1);

use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

dataset('multiline', [
    'single line' => [false, 'craft-input'],
    'multiline' => [true, 'craft-textarea'],
]);

it('renders the enlarged UI mode as a large control', function (bool $multiline, string $tag) {
    $field = new PlainText(['uiMode' => 'enlarged', 'multiline' => $multiline]);
    $control = $field->uiControl(new FieldContext('text'));
    $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('Text', $control)]), new UiContext);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($control->props()['enlarged'] ?? false)->toBeTrue()
        ->and($crawler->filter($tag)->attr('size'))->toBe('large');
})->with('multiline');

it('renders the normal UI mode at the default size', function (bool $multiline, string $tag) {
    $field = new PlainText(['multiline' => $multiline]);
    $control = $field->uiControl(new FieldContext('text'));
    $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('Text', $control)]), new UiContext);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($control->props())->not->toHaveKey('enlarged')
        ->and($crawler->filter($tag)->attr('size'))->toBeNull();
})->with('multiline');
