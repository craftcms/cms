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

it('shows the characters left for a character limit', function (bool $multiline, string $tag) {
    $field = new PlainText(['charLimit' => 70, 'multiline' => $multiline]);
    $control = $field->uiControl(new FieldContext('text'));
    $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('Text', $control)]), new UiContext);
    $host = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter($tag);

    expect($control->props()['showCharsLeft'] ?? false)->toBeTrue()
        ->and($host->attr('maxlength'))->toBe('70')
        ->and($host->attr('show-chars-left'))->not->toBeNull();
})->with('multiline');

it('shows no characters left for a byte limit', function (bool $multiline, string $tag) {
    $field = new PlainText(['byteLimit' => 70, 'multiline' => $multiline]);
    $control = $field->uiControl(new FieldContext('text'));
    $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('Text', $control)]), new UiContext);
    $host = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter($tag);

    expect($control->props())->not->toHaveKey('showCharsLeft')
        ->and($host->attr('show-chars-left'))->toBeNull();
})->with('multiline');
