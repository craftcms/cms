<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Mcp\Capabilities\Assets;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
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

    app()->instance(UrlValidator::class, new UrlValidator(resolver: static fn (string $host): array => match ($host) {
        'internal.test' => ['127.0.0.1'],
        'mixed.test' => ['93.184.216.34', '10.0.0.1'],
        default => ['93.184.216.34'],
    }));
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

    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    $created = McpRequest::send($this, 'tools/call', [
        'name' => 'assets.create',
        'arguments' => ['uploadId' => $upload['id'], 'attributes' => ['title' => 'Uploaded document']],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');
    $asset = Asset::findOne($created['asset']['id']);

    expect($asset->title)->toBe('Uploaded document')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc')
        ->and(UploadSession::find($upload['id']))->toBeNull()
        ->and(fn () => $assets->create($upload['id']))->toThrow(ToolCallException::class);
});

it('accepts client file references through MCP and stores the downloaded bytes', function (array $reference, ?string $filename): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    Http::fake(['https://files.example.test/*' => Http::response('Downloaded content')]);
    $temporaryFiles = File::files(Path::temp());

    $tools = McpRequest::send($this, 'tools/list')->assertOk()->json('result.tools');
    $create = collect($tools)->firstWhere('name', 'assets.create');

    expect($create['_meta']['openai/fileParams'])->toBe(['file'])
        ->and($create['inputSchema']['properties']['file']['properties'])->toHaveKeys(['download_url', 'file_id', 'mime_type', 'file_name'])
        ->and($create['inputSchema']['properties']['file']['required'])->toBe(['download_url', 'file_id']);

    $created = McpRequest::send($this, 'tools/call', [
        'name' => 'assets.create',
        'arguments' => array_filter([
            'file' => $reference,
            'folderId' => $this->folder->id,
            'filename' => $filename,
            'attributes' => ['title' => 'Imported document', 'alt' => 'Document description'],
        ], static fn (mixed $value): bool => $value !== null),
    ])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.asset.title', 'Imported document')
        ->assertJsonPath('result.structuredContent.asset.alt', 'Document description')
        ->json('result.structuredContent.asset');
    $asset = Asset::findOne($created['id']);

    expect($asset->getFilename())->toBe('imported.txt')
        ->and($asset->getMimeType())->toBe('text/plain')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('Downloaded content')
        ->and(UploadSession::count())->toBe(0)
        ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
})->with([
    'client filename and untrusted MIME type' => [[
        'download_url' => 'https://files.example.test/download?signature=secret',
        'file_id' => 'file_example',
        'file_name' => 'imported.txt',
        'mime_type' => 'image/png',
    ], null],
    'optional filename and MIME type omitted' => [[
        'download_url' => 'https://files.example.test/download',
        'file_id' => 'file_example',
    ], 'imported.txt'],
]);

it('ingests an image reference through the shared asset upload workflow', function (): void {
    $image = File::get(dirname(__DIR__, 2).'/_data/assets/files/google.png');
    Http::fake(['https://files.example.test/*' => Http::response($image)]);
    $temporaryFiles = File::files(Path::temp());

    $created = app(Assets::class)->create(
        file: ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_image', 'file_name' => 'image.png'],
        volumeId: $this->folder->volumeId,
        attributes: ['alt' => 'Example image'],
    )['asset'];
    $asset = Asset::findOne($created['id']);
    $stored = Storage::disk('upload-destination')->get($asset->getPath());

    expect($asset->getMimeType())->toBe('image/png')
        ->and($asset->alt)->toBe('Example image')
        ->and(getimagesizefromstring($stored)['mime'])->toBe('image/png')
        ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
});

it('releases the downloaded file when asset validation fails', function (): void {
    Http::fake(['https://files.example.test/*' => Http::response('Downloaded content')]);
    $temporaryFiles = File::files(Path::temp());

    expect(fn () => app(Assets::class)->create(
        file: ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_example', 'file_name' => 'imported.txt'],
        folderId: $this->folder->id,
        attributes: ['title' => str_repeat('a', 256)],
    ))->toThrow(ToolCallException::class);

    expect(Asset::find()->count())->toBe(0)
        ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
});

it('rejects unsafe file references without making a download request', function (string $url): void {
    Http::fake();

    expect(fn () => app(Assets::class)->create(
        file: ['download_url' => $url, 'file_id' => 'file_example', 'file_name' => 'imported.txt'],
        folderId: $this->folder->id,
    ))->toThrow(ToolCallException::class);

    Http::assertNothingSent();
    expect(Asset::find()->count())->toBe(0);
})->with([
    'unencrypted URL' => 'http://files.example.test/download',
    'embedded credentials' => 'https://user:password@files.example.test/download',
    'loopback hostname' => 'https://internal.test/download',
    'mixed public and private DNS addresses' => 'https://mixed.test/download',
    'cloud metadata' => 'https://metadata.google.internal/download',
    'local path' => 'file:///etc/passwd',
]);

it('checks destination permissions before downloading a file reference', function (): void {
    $user = User::query()->firstOrFail();
    $user->admin = false;
    $user->save();
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp', 'useCraftMcp']);
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);
    Http::fake();

    expect(fn () => app(Assets::class)->create(
        file: ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_example', 'file_name' => 'imported.txt'],
        folderId: $this->folder->id,
    ))->toThrow(ToolCallException::class, 'unauthorized');

    Http::assertNothingSent();
});

it('rejects invalid source combinations without downloading or consuming an upload', function (): void {
    Http::fake();
    $assets = app(Assets::class);
    $upload = $assets->prepareUpload('example.txt', 3, folderId: $this->folder->id)['upload'];
    $file = ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_example', 'file_name' => 'imported.txt'];

    expect(fn () => $assets->create())->toThrow(ToolCallException::class, 'exactly one')
        ->and(fn () => $assets->create($upload['id'], file: $file, folderId: $this->folder->id))->toThrow(ToolCallException::class, 'exactly one')
        ->and(fn () => $assets->create($upload['id'], folderId: $this->folder->id))->toThrow(ToolCallException::class, 'already specifies');

    Http::assertNothingSent();
    expect(UploadSession::find($upload['id']))->not->toBeNull();
});

it('does not save assets or retain temporary files after a failed download', function (string $body, int $status, array $headers): void {
    Cms::config()->maxUploadFileSize = 4;
    Http::fake(['https://files.example.test/*' => $status === 0
        ? Http::failedConnection('Cannot connect to https://files.example.test/download?signature=secret')
        : Http::response($body, $status, $headers)]);
    $temporaryFiles = File::files(Path::temp());

    try {
        app(Assets::class)->create(
            file: ['download_url' => 'https://files.example.test/download?signature=secret', 'file_id' => 'file_example', 'file_name' => 'imported.txt'],
            folderId: $this->folder->id,
        );

        $this->fail('The invalid download was accepted.');
    } catch (ToolCallException $exception) {
        expect($exception->getMessage())->not->toContain('signature=secret');
    }

    expect(Asset::find()->count())->toBe(0)
        ->and(Storage::disk('upload-destination')->allFiles())->toBe([])
        ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
})->with([
    'oversized body without Content-Length' => ['12345', 200, []],
    'empty body' => ['', 200, []],
    'redirect to an internal address' => ['', 302, ['Location' => 'https://internal.test/download']],
    'expired file reference' => ['gone', 403, []],
    'connection failure' => ['', 0, []],
]);
