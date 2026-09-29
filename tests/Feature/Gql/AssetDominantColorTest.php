<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Gql\Arguments\Mutations\Asset as AssetMutationArguments;

it('queries an image’s dominant color', function (?string $stored, ?string $expected) {
    $asset = Asset::factory()->createElement(['kind' => 'image']);
    Asset::whereKey($asset->id)->update(['dominantColor' => $stored]);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { dominantColor } }")
        ->assertOk()
        ->assertJsonPath('data.asset.dominantColor', $expected);
})->with([
    'known' => ['#3a6ea5', '#3a6ea5'],
    'inconclusive' => ['#------', null],
    'not determined yet' => [null, null],
]);

it('can’t be set by mutations', function () {
    expect(AssetMutationArguments::getArguments())->not->toHaveKey('dominantColor');
});
