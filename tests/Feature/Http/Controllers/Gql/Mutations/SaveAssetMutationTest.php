<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    gqlDisablePublicToken();

    $root = storage_path('framework/testing/graphql-mutations/assets-'.bin2hex(random_bytes(4)));

    config()->set('filesystems.disks.graphql-mutation-disk', [
        'driver' => 'local',
        'root' => $root,
    ]);

    $this->volume = Volume::factory()->create([
        'name' => 'Uploads',
        'handle' => 'uploads',
        'fs' => 'disk:graphql-mutation-disk',
    ]);
    $this->rootFolder = app(Folders::class)->getRootFolderByVolumeId($this->volume->id);

    gqlActivateFullAccessSchema();
});

it('creates an asset with the save asset mutation', function () {
    graphQL(<<<'GRAPHQL'
mutation {
  save_uploads_Asset(
    _file: {
      fileData: "data:text/plain;base64,SGVsbG8gZnJvbSBHcmFwaFFM"
      filename: "mutation.txt"
    }
    alt: "GraphQL upload"
  ) {
    filename
    alt
  }
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertExactJson([
            'data' => [
                'save_uploads_Asset' => [
                    'filename' => 'mutation.txt',
                    'alt' => 'GraphQL upload',
                ],
            ],
        ]);
});
