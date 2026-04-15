<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndexSourcesController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->postIndexSourceAction = fn (string $path, array $payload = []) => postJson(
        action([ElementIndexSourcesController::class, match ($path) {
            'source-path' => 'sourcePath',
            'source-attribute-info' => 'sourceAttributeInfo',
            'get-source-tree-html' => 'getSourceTreeHtml',
        }]),
        array_merge([
            'context' => ElementSources::CONTEXT_INDEX,
            'elementType' => Entry::class,
            'source' => '*',
            'viewState' => [
                'mode' => 'table',
                'static' => false,
            ],
        ], $payload),
        [
            'Accept' => 'application/json',
        ],
    );
});

it('returns source attribute info for the selected source', function () {
    $response = ($this->postIndexSourceAction)('source-attribute-info');

    $response->assertOk()
        ->assertJsonStructure([
            'sortOptions',
            'tableColumns',
            'defaultTableColumns',
        ]);

    expect($response->json('sortOptions'))->toBeArray()
        ->and($response->json('defaultTableColumns'))->toContain('status');
});

it('returns source path info for asset folder steps', function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/element-index-controller-test/test-disk'),
    ]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolder::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Docs',
        'path' => 'docs/',
    ]);

    postJson(action([ElementIndexSourcesController::class, 'sourcePath']), [
        'context' => ElementSources::CONTEXT_INDEX,
        'elementType' => Asset::class,
        'source' => "volume:$volume->uid",
        'stepKey' => "folder:$folder->uid",
    ], [
        'Accept' => 'application/json',
    ])->assertOk()
        ->assertJsonPath('sourcePath.0.key', "volume:$volume->uid")
        ->assertJsonPath('sourcePath.0.folderId', $folder->id);
});

it('returns source tree html', function () {
    ($this->postIndexSourceAction)('get-source-tree-html')->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, 'sources-list'));
});
