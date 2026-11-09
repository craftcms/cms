<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Mcp\Capabilities\AssetFolders;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    config()->set('filesystems.disks.asset-folders', ['driver' => 'local', 'root' => storage_path('framework/testing/asset-folders')]);
    Storage::fake('asset-folders');

    $volume = Volume::factory()->create(['fs' => 'asset-folders']);
    $this->rootFolder = VolumeFolder::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => null,
        'path' => '',
    ]);
});

it('manages asset folders through MCP', function () {
    $assetFolders = app(AssetFolders::class);

    $created = $assetFolders->create('Product Images', $this->rootFolder->id)['folder'];
    $listed = $assetFolders->list(['parentId' => $this->rootFolder->id]);
    $updated = $assetFolders->update('Product Photos', id: $created['id'])['folder'];
    $fetched = $assetFolders->get(uid: $created['uid'])['folder'];
    $deleted = $assetFolders->delete(id: $created['id']);

    expect($listed['folders'])->toContain($created)
        ->and($updated['name'])->toBe('Product-Photos')
        ->and($fetched)->toBe($updated)
        ->and($deleted)->toBe(['deleted' => true])
        ->and(VolumeFolder::find($created['id']))->toBeNull();
});

it('lists root folders for a null parentId', function () {
    $volume = Volume::factory()->create(['fs' => 'asset-folders']);
    $otherRoot = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'parentId' => null, 'path' => '']);
    $subfolder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'parentId' => $otherRoot->id, 'name' => 'Sub', 'path' => 'Sub/']);

    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:read'], 'craft-mcp');
    $ids = fn (array $criteria): array => array_column(McpRequest::send($this, 'tools/call', [
        'name' => 'asset_folders_list',
        'arguments' => ['criteria' => $criteria],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.folders'), 'id');

    expect($ids(['parentId' => null]))->toContain($this->rootFolder->id, $otherRoot->id)->not->toContain($subfolder->id)
        ->and($ids(['parentId' => null, 'volumeId' => $volume->id]))->toBe([$otherRoot->id]);
});
