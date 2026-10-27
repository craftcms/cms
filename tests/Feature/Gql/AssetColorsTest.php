<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Gql\Arguments\Mutations\Asset as AssetMutationArguments;

it('queries an image’s colors', function (?array $stored, ?array $expected) {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => $stored === null ? null : json_encode(['colors' => $stored])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { colors { dominant grid left right top bottom } } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.colors', $expected);
})->with([
    'sampled' => [
        ['dominant' => '#3a6ea5', 'grid' => [['#ff0000', '#3a6ea5', '#0000ff'], ['#990000', '#3a6ea5', '#000099']]],
        [
            'dominant' => '#3a6ea5',
            'grid' => [['#ff0000', '#3a6ea5', '#0000ff'], ['#990000', '#3a6ea5', '#000099']],
            'left' => '#d40000',
            'right' => '#0000d4',
            'top' => '#9f40b4',
            'bottom' => '#614084',
        ],
    ],
    'inconclusive' => [
        ['dominant' => null, 'grid' => []],
        ['dominant' => null, 'grid' => [], 'left' => null, 'right' => null, 'top' => null, 'bottom' => null],
    ],
    'not sampled yet' => [null, null],
]);

it('queries an image’s placeholder data URL', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => json_encode(['colors' => ['dominant' => '#3a6ea5', 'grid' => [['#3a6ea5']]]])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { placeholderDataUrl } }")
        ->assertOk()
        ->assertJsonPath('data.asset.placeholderDataUrl', fn (?string $url): bool => str_starts_with((string) $url, 'data:image/png;base64,'));
});

it('has no placeholder data URL for an image whose colors aren’t known', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { placeholderDataUrl } }")
        ->assertOk()
        ->assertJsonPath('data.asset.placeholderDataUrl', null);
});

it('queries an image’s BlurHash', function (?array $stored, bool $hasHash) {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => $stored === null ? null : json_encode(['colors' => $stored])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { blurhash } }")
        ->assertOk()
        ->assertJsonPath('data.asset.blurhash', fn (?string $hash): bool => $hasHash ? strlen((string) $hash) === 28 : $hash === null);
})->with([
    'sampled' => [['dominant' => '#3a6ea5', 'grid' => array_fill(0, 3, array_fill(0, 4, '#3a6ea5'))], true],
    'not sampled yet' => [null, false],
]);

it('can’t be set by mutations', function () {
    expect(AssetMutationArguments::getArguments())
        ->not->toHaveKey('colors')
        ->not->toHaveKey('analysis')
        ->not->toHaveKey('placeholderDataUrl')
        ->not->toHaveKey('blurhash');
});

it('queries an image’s palette and the names of its colors', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => json_encode(['colors' => ['dominant' => '#c81e28', 'grid' => [], 'palette' => ['#c81e28', '#1e50c8', '#e01818']]])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { colors { palette labels } } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.colors.palette', ['#c81e28', '#1e50c8', '#e01818'])
        ->assertJsonPath('data.asset.analysis.colors.labels', ['red', 'blue']);
});

it('makes images searchable by the names of their colors', function () {
    $image = Asset::factory()->createElement(['kind' => 'image']);
    $document = Asset::factory()->createElement(['kind' => 'pdf']);
    $colors = json_encode(['colors' => ['dominant' => '#c81e28', 'grid' => [], 'palette' => ['#c81e28', '#1e50c8']]]);
    Asset::whereKey([$image->id, $document->id])->update(['analysis' => $colors]);

    expect(AssetElement::find()->id($image->id)->one()->getSearchKeywords('colors'))->toBe('red blue')
        ->and(AssetElement::find()->id($document->id)->one()->getSearchKeywords('colors'))->toBe('');
});

it('queries how light an image’s regions are, and which text color is legible over them', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    $luminance = [[[0.0, 0.05], [0.01, 0.04]], [[0.8, 1.0], [0.9, 1.0]]];
    Asset::whereKey($asset->id)->update(['analysis' => json_encode(['colors' => ['grid' => [['#000000', '#000000'], ['#ffffff', '#ffffff']], 'luminance' => $luminance]])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { colors { luminance top: textColor(region: \"top\") { color contrast } anywhere: textColor { color contrast } } } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.colors.luminance', [[[0, 0.05], [0.01, 0.04]], [[0.8, 1], [0.9, 1]]])
        ->assertJsonPath('data.asset.analysis.colors.top', ['color' => '#ffffff', 'contrast' => 10.5])
        ->assertJsonPath('data.asset.analysis.colors.anywhere', ['color' => '#ffffff', 'contrast' => 1]);
});

it('queries an image’s hash', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => json_encode(['hash' => 'fcfbf8e7ecdad4a3'])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { hash } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.hash', 'fcfbf8e7ecdad4a3');
});

it('queries an image’s embedded metadata', function () {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['analysis' => json_encode(['metadata' => ['creator' => 'Ada Park', 'keywords' => ['harbor'], 'hasLocation' => true]])]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { metadata { creator copyright keywords hasLocation } } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.metadata', ['creator' => 'Ada Park', 'copyright' => null, 'keywords' => ['harbor'], 'hasLocation' => true]);
});
