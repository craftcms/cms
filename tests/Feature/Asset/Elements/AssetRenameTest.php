<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;

// #19950
it('keeps the file extension when an asset is renamed without one', function (string $filename, string $newFilename, string $expected) {
    $asset = AssetModel::factory()->createElement(['filename' => $filename]);
    $asset->newFilename = $newFilename;

    expect($asset->beforeSave(false))->toBeTrue()
        ->and($asset->newLocation)->toBe("{folder:{$asset->folderId}}$expected");
})->with([
    'no extension' => ['Test.pdf', 'Test-Renamed', 'Test-Renamed.pdf'],
    'trailing dot' => ['Test.pdf', 'Test-Renamed.', 'Test-Renamed.pdf'],
    'uppercase extension' => ['Test.PDF', 'Test-Renamed', 'Test-Renamed.PDF'],
    'new extension' => ['Test.pdf', 'Test-Renamed.txt', 'Test-Renamed.txt'],
    'no extension to keep' => ['Test', 'Test-Renamed', 'Test-Renamed'],
]);
