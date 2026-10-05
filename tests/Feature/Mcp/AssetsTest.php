<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Mcp\Capabilities\Assets;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mcp\Exception\ToolCallException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    config()->set('filesystems.disks.upload-parts', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-parts')]);
    config()->set('filesystems.disks.upload-destination', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-destination')]);
    Storage::fake('upload-parts');
    Storage::fake('upload-destination');
    Cms::config()->uploadSessionDisk = 'upload-parts';
    Cms::config()->uploadChunkSize = 3;

    $volume = Volume::factory()->create(['fs' => 'upload-destination']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
});

it('creates an asset from a completed upload session once', function () {
    $assets = app(Assets::class);
    $upload = $assets->prepareUpload('example.txt', 3, folderId: $this->folder->id)['upload'];
    $request = Request::create($upload['urls']['transfer'], 'PATCH', server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0',
        'HTTP_UPLOAD_OFFSET' => 0,
    ], content: 'abc');
    $request->setUserResolver(static fn (): User => User::query()->firstOrFail());

    app(Uploads::class)->transfer($request, $upload['id']);
    $created = $assets->create($upload['id'], ['title' => 'Uploaded document']);
    $asset = Asset::findOne($created['asset']['id']);

    expect($asset->title)->toBe('Uploaded document')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc')
        ->and(UploadSession::find($upload['id']))->toBeNull()
        ->and(fn () => $assets->create($upload['id']))->toThrow(ToolCallException::class);
});
