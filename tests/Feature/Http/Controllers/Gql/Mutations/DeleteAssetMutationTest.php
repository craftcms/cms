<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    gqlDisablePublicToken();

    config()->set('filesystems.disks.graphql-mutation-delete-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/graphql-mutations/delete-assets'),
    ]);

    $this->volume = Volume::factory()->create([
        'name' => 'Uploads',
        'handle' => 'uploads',
        'fs' => 'disk:graphql-mutation-delete-disk',
    ]);
    $this->rootFolder = app(Folders::class)->getRootFolderByVolumeId($this->volume->id);

    gqlActivateFullAccessSchema();
});

it('deletes an asset with the delete asset mutation', function () {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->rootFolder->id,
        'filename' => 'delete-me.txt',
        'kind' => 'text',
    ]);

    graphQL(<<<GRAPHQL
mutation {
  deleteAsset(id: {$asset->id})
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.deleteAsset', true);

    expect(Asset::find()->id($asset->id)->status(null)->one())->toBeNull();
});
