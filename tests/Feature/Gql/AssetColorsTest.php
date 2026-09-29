<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Gql\Arguments\Mutations\Asset as AssetMutationArguments;

it('queries an image’s colors', function (?array $stored, ?array $expected) {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['colors' => $stored === null ? null : json_encode($stored)]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { colors { dominant grid left right top bottom } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.colors', $expected);
})->with([
    'sampled' => [
        ['dominant' => '#3a6ea5', 'grid' => [['#ff0000', '#3a6ea5', '#0000ff'], ['#990000', '#3a6ea5', '#000099']]],
        [
            'dominant' => '#3a6ea5',
            'grid' => [['#ff0000', '#3a6ea5', '#0000ff'], ['#990000', '#3a6ea5', '#000099']],
            'left' => '#cc0000',
            'right' => '#0000cc',
            'top' => '#68258c',
            'bottom' => '#46256a',
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
    Asset::whereKey($asset->id)->update(['colors' => json_encode(['dominant' => '#3a6ea5', 'grid' => [['#3a6ea5']]])]);
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

it('can’t be set by mutations', function () {
    expect(AssetMutationArguments::getArguments())
        ->not->toHaveKey('colors')
        ->not->toHaveKey('placeholderDataUrl');
});
