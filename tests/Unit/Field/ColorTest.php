<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Data\ColorData;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Symfony\Component\DomCrawler\Crawler;

it('defaults the GraphQL mode to full data for new fields only', function () {
    expect(new Color()->fullGraphqlData)->toBeTrue()
        ->and(new Color(['id' => 1])->fullGraphqlData)->toBeFalse()
        ->and(new Color(['id' => 1, 'graphqlMode' => 'full'])->fullGraphqlData)->toBeTrue()
        ->and(Fields::createField(['type' => Color::class, 'graphqlMode' => 'hex'])->fullGraphqlData)->toBeFalse();
});

it('returns hex strings in hex-only GraphQL mode', function () {
    expect(new Color(['fullGraphqlData' => false])->getContentGqlType())->toBe(Type::string());
});

it('returns full color data in full GraphQL mode', function () {
    $schema = new Schema([
        'query' => new ObjectType([
            'name' => 'Query',
            'fields' => [
                'color' => [
                    'type' => new Color()->getContentGqlType(),
                    'resolve' => fn () => new ColorData('#0000ff80', 'Blue'),
                ],
            ],
        ]),
    ]);

    $result = GraphQL::executeQuery($schema, <<<'GQL'
{
  color {
    hex
    opaqueHex: hex(withAlpha: false)
    label
    red
    blue
    alpha
    hue
    rgba
    rgbChannels
    hsla
    oklch
    contrastColor
    contrastRatio(with: "#ffffff")
  }
}
GQL)->toArray();

    expect($result)->not->toHaveKey('errors')
        ->and($result['data']['color'])->toMatchArray([
            'hex' => '#0000ff80',
            'opaqueHex' => '#0000ff',
            'label' => 'Blue',
            'red' => 0,
            'blue' => 255,
            'alpha' => 0.5,
            'hue' => 240,
            'rgba' => 'rgba(0,0,255,0.5)',
            'rgbChannels' => '0 0 255',
            'hsla' => 'hsla(240,100%,50%,0.5)',
            'oklch' => 'oklch(45.2% 0.3132 264.05 / 0.5)',
            'contrastColor' => '#ffffff',
        ])
        ->and($result['data']['color']['contrastRatio'])->toEqualWithDelta(8.59, 0.005);
});

it('offers opacity in the color picker only when custom alpha is allowed', function () {
    $field = new Color(['allowCustomColors' => true, 'allowCustomAlpha' => true]);
    $control = $field->uiControl(new FieldContext('color', $field->normalizeValue('#ff000080', null)));

    expect($control->props()['alpha'] ?? false)->toBeTrue()
        ->and($control->getValue())->toBe('#ff000080')
        ->and($control->inputSchema()['pattern'])->toBe('^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$');

    $field->allowCustomAlpha = false;
    $control = $field->uiControl(new FieldContext('color'));

    expect($control->props()['alpha'] ?? false)->toBeFalse()
        ->and($control->inputSchema()['pattern'])->toBe('^#[0-9a-fA-F]{6}$');
});

it('normalizes translucent palette colors', function () {
    $field = new Color(['palette' => [['color' => 'F008', 'label' => 'Red'], ['color' => '#00FF00FF', 'label' => 'Green']]]);

    expect(array_column($field->palette, 'color'))->toBe(['#ff000088', '#00ff00'])
        ->and($field->normalizeValue('#ff000088', null)->label)->toBe('Red');
});

it('shows the custom opacity setting only when custom colors are allowed', function (bool $allowCustomColors) {
    $context = new UiContext(namespace: 'settings');
    $payload = app(UiResolver::class)->resolve(new Color(['allowCustomColors' => $allowCustomColors])->settingsUi($context), $context);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[name="settings[allowCustomAlpha]"]')->count() > 0)->toBe($allowCustomColors)
        ->and($crawler->filter('[name="settings[graphqlMode]"]')->count())->toBeGreaterThan(0);
})->with([true, false]);
