<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

test('getPreviewHtml returns unformatted numbers as strings', function (int|float $value, string $expected) {
    $field = new Number(['previewFormat' => Number::FORMAT_NONE]);

    expect($field->getPreviewHtml($value, new Entry))->toBe($expected);
})->with([
    'int' => [1889, '1889'],
    'float' => [1.5, '1.5'],
]);

test('the control shows the prefix and suffix', function () {
    $props = new Number(['prefix' => '€', 'suffix' => 'kg'])->uiControl(new FieldContext('weight'))->props();

    expect($props['prefix'])->toBe('€')
        ->and($props['suffix'])->toBe('kg');
});

test('the control shows the value formatted for the formatting locale, and reads it back', function (array $config, int|float $value, string $locale, string $expected) {
    $field = new Number($config);

    $html = I18N::withLocale('en-US', $locale, function () use ($field, $value) {
        $payload = app(UiResolver::class)->resolve(
            Ui::make([UiField::make()->control($field->uiControl(new FieldContext('number', $value)))]),
            new UiContext(namespace: 'fields'),
        );

        return app(UiHtmlRenderer::class)->render($payload);
    });
    $crawler = new Crawler($html);
    $input = $crawler->filter('input[type="text"][name="fields[number][value]"]');

    expect($input->attr('value'))->toBe($expected)
        ->and($crawler->filter("input[type=\"hidden\"][name=\"fields[number][locale]\"][value=\"$locale\"]"))->toHaveCount(1)
        ->and($field->normalizeValue(['value' => $expected, 'locale' => $locale], null))->toBe($value);
})->with([
    'grouped integer' => [[], 104233, 'en-US', '104,233'],
    'zero padded to the decimals' => [['decimals' => 2], 0, 'en-US', '0.00'],
    'German separators' => [['decimals' => 2], 1234.5, 'de-DE', '1.234,50'],
    'Swedish minus sign' => [['decimals' => 2, 'min' => null], -1234.5, 'sv-SE', "\u{2212}1\u{00A0}234,50"],
    'Arabic digits' => [['decimals' => 2], 1234.5, 'ar-EG', "\u{0661}\u{066C}\u{0662}\u{0663}\u{0664}\u{066B}\u{0665}\u{0660}"],
    'unformatted, with the locale’s decimal symbol' => [['decimals' => 2, 'previewFormat' => Number::FORMAT_NONE], 1234.5, 'de-DE', '1234,50'],
]);

test('the control keeps a native number input when the field has a step', function () {
    $props = I18N::withLocale('en-US', 'de-DE', fn () => new Number(['step' => 0.5, 'decimals' => 1])
        ->uiControl(new FieldContext('number', 1234.5))
        ->props());

    expect($props['inputType'])->toBe('number')
        ->and($props)->not->toHaveKey('locale');
});

test('the control posts a bare number without a locale, as a raw number', function () {
    $html = I18N::withLocale('en-US', 'de-DE', function () {
        $payload = app(UiResolver::class)->resolve(
            Ui::make([UiField::make()->control(new Number(['decimals' => 1])->uiControl(new FieldContext('number', 1)))]),
            new UiContext(namespace: 'fields', values: ['fields' => ['number' => 1234.5]]),
        );

        return app(UiHtmlRenderer::class)->render($payload);
    });
    $crawler = new Crawler($html);

    expect($crawler->filter('input[name="fields[number]"]')->attr('value'))->toBe('1234.5')
        ->and($crawler->filter('input[name="fields[number][locale]"]'))->toHaveCount(0);
});
