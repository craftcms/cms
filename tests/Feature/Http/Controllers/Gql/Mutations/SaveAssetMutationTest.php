<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\User\Elements\User;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
        'fs' => 'graphql-mutation-disk',
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

it('creates an asset from a remote URL with the save asset mutation', function (): void {
    app()->instance(UrlValidator::class, new UrlValidator(resolver: static fn (string $host): array => ['93.184.216.34']));
    Http::fake(['http://files.example.test/mutation.txt' => Http::response('Remote GraphQL content')]);

    graphQL(<<<'GRAPHQL'
mutation {
  save_uploads_Asset(
    _file: {
      url: "http://files.example.test/mutation.txt"
      filename: "remote.txt"
    }
  ) {
    filename
  }
}
GRAPHQL)
        ->assertOk()
        ->assertExactJson(['data' => ['save_uploads_Asset' => ['filename' => 'remote.txt']]]);

    $asset = Asset::find()->filename('remote.txt')->one();
    expect(Storage::disk('graphql-mutation-disk')->get($asset->getPath()))->toBe('Remote GraphQL content');
});
