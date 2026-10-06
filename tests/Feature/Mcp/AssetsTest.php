<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Mcp\Capabilities\Assets;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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

describe('file replacement', function (): void {
    beforeEach(function (): void {
        Http::fake(['https://files.example.test/original' => Http::response('Original content')]);
        $created = app(Assets::class)->create(
            file: ['download_url' => 'https://files.example.test/original', 'file_id' => 'original', 'file_name' => 'original.txt'],
            folderId: $this->folder->id,
            attributes: ['title' => 'Existing document', 'alt' => 'Existing description'],
        )['asset'];
        $this->asset = Asset::findOne($created['id']);

        $this->transferUpload = function (array $upload): void {
            $request = Request::create($upload['urls']['transfer'], 'PATCH', server: [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_TUS_RESUMABLE' => '1.0.0',
                'HTTP_UPLOAD_OFFSET' => 0,
            ], content: 'abc');
            $request->setUserResolver(static fn (): User => User::query()->firstOrFail());

            expect(app(Uploads::class)->transfer($request, $upload['id'])->getStatusCode())->toBe(204);
        };
    });

    it('replaces the stored bytes through MCP while preserving the asset identity, metadata and entry references', function (string $source): void {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
        Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
        $originalId = $this->asset->id;
        $originalUid = $this->asset->uid;
        $originalPath = $this->asset->getPath();
        $entry = EntryModel::factory()
            ->withField('attachment', AssetsField::class, value: [$originalId])
            ->createElementWithFields()->element;
        expect(Entry::findOne($entry->id)->getFieldValue('attachment')->ids())->toBe([$originalId]);
        $temporaryFiles = File::files(Path::temp());
        $arguments = ['assetId' => $originalId];

        if ($source === 'upload') {
            $upload = McpRequest::send($this, 'tools/call', [
                'name' => 'assets.upload.prepare',
                'arguments' => ['filename' => 'replacement.txt', 'size' => 3, 'assetId' => $originalId],
            ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.upload');
            ($this->transferUpload)($upload);
            $arguments['uploadId'] = $upload['id'];
        } else {
            Http::fake(['https://files.example.test/replacement' => Http::response('abc')]);
            $arguments['file'] = [
                'download_url' => 'https://files.example.test/replacement',
                'file_id' => 'replacement',
                'mime_type' => 'image/png',
            ];

            if ($source === 'file_name') {
                $arguments['file']['file_name'] = 'replacement.txt';
            } else {
                $arguments['file']['file_name'] = 'ignored.txt';
                $arguments['filename'] = 'replacement.txt';
            }

            $tools = McpRequest::send($this, 'tools/list')->assertOk()->json('result.tools');
            expect(collect($tools)->firstWhere('name', 'assets.replace')['_meta']['openai/fileParams'])->toBe(['file']);
        }

        McpRequest::send($this, 'tools/call', [
            'name' => 'assets.replace',
            'arguments' => $arguments,
        ])
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.asset.id', $originalId)
            ->assertJsonPath('result.structuredContent.asset.uid', $originalUid)
            ->assertJsonPath('result.structuredContent.asset.title', 'Existing document')
            ->assertJsonPath('result.structuredContent.asset.alt', 'Existing description');
        $asset = Asset::findOne($originalId);

        expect($asset->getFilename())->toBe('replacement.txt')
            ->and($asset->folderId)->toBe($this->folder->id)
            ->and($asset->getMimeType())->toBe('text/plain')
            ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc')
            ->and(Storage::disk('upload-destination')->exists($originalPath))->toBeFalse()
            ->and(Asset::find()->count())->toBe(1)
            ->and(Entry::findOne($entry->id)->getFieldValue('attachment')->ids())->toBe([$originalId])
            ->and(UploadSession::count())->toBe(0)
            ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
    })->with(['file_name', 'filename override', 'upload']);

    it('rejects ambiguous sources and filename overrides without consuming uploads or downloading files', function (): void {
        Http::fake();
        $assets = app(Assets::class);
        $upload = $assets->prepareUpload('replacement.txt', 3, assetId: $this->asset->id)['upload'];
        ($this->transferUpload)($upload);
        $file = ['download_url' => 'https://files.example.test/replacement', 'file_id' => 'replacement', 'file_name' => 'replacement.txt'];

        expect(fn () => $assets->replace($this->asset->id))->toThrow(ToolCallException::class, 'exactly one')
            ->and(fn () => $assets->replace($this->asset->id, $upload['id'], $file))->toThrow(ToolCallException::class, 'exactly one')
            ->and(fn () => $assets->replace($this->asset->id, $upload['id'], filename: 'override.txt'))->toThrow(ToolCallException::class, 'already specifies')
            ->and(fn () => $assets->replace($this->asset->id, file: array_diff_key($file, ['file_name' => true])))->toThrow(ToolCallException::class, 'Provide filename');

        Http::assertNothingSent();
        expect(UploadSession::find($upload['id']))->not->toBeNull()
            ->and(Storage::disk('upload-destination')->get($this->asset->getPath()))->toBe('Original content');

        $assets->replace($this->asset->id, $upload['id']);

        expect(Storage::disk('upload-destination')->get(Asset::findOne($this->asset->id)->getPath()))->toBe('abc')
            ->and(UploadSession::find($upload['id']))->toBeNull();
    });

    it('rejects replacement preparation with an upload destination', function (string $destination): void {
        expect(fn () => app(Assets::class)->prepareUpload('replacement.txt', 3, ...[
            'assetId' => $this->asset->id,
            $destination => $destination === 'folderId' ? $this->folder->id : $this->folder->volumeId,
        ]))->toThrow(ToolCallException::class, 'either assetId');

        expect(UploadSession::count())->toBe(0);
    })->with(['folderId', 'volumeId']);

    it('rejects mismatched sessions while keeping their files available for the intended operation', function (string $mismatch): void {
        $assets = app(Assets::class);
        $upload = $mismatch === 'creation upload used for replacement'
            ? $assets->prepareUpload('replacement.txt', 3, folderId: $this->folder->id)['upload']
            : $assets->prepareUpload('replacement.txt', 3, assetId: $this->asset->id)['upload'];
        ($this->transferUpload)($upload);

        $otherAsset = null;

        if ($mismatch === 'wrong replacement target') {
            Http::fake(['https://files.example.test/other' => Http::response('Other content')]);
            $created = $assets->create(
                file: ['download_url' => 'https://files.example.test/other', 'file_id' => 'other', 'file_name' => 'other.txt'],
                folderId: $this->folder->id,
            )['asset'];
            $otherAsset = Asset::findOne($created['id']);
        }

        if ($mismatch === 'replacement upload used for creation') {
            expect(fn () => $assets->create($upload['id']))->toThrow(ToolCallException::class, 'operation');
        } else {
            $targetId = $otherAsset?->id ?? $this->asset->id;
            expect(fn () => $assets->replace($targetId, $upload['id']))->toThrow(ToolCallException::class, $mismatch === 'wrong replacement target' ? 'different asset' : 'operation');
        }

        expect(UploadSession::find($upload['id']))->not->toBeNull()
            ->and(Storage::disk('upload-destination')->get($this->asset->getPath()))->toBe('Original content');

        if ($otherAsset !== null) {
            expect(Storage::disk('upload-destination')->get($otherAsset->getPath()))->toBe('Other content');
        }

        $result = $mismatch === 'creation upload used for replacement'
            ? $assets->create($upload['id'])
            : $assets->replace($this->asset->id, $upload['id']);
        $asset = Asset::findOne($result['asset']['id']);

        expect(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc')
            ->and(UploadSession::find($upload['id']))->toBeNull();
    })->with(['creation upload used for replacement', 'replacement upload used for creation', 'wrong replacement target']);

    it('uses replacement permissions for preparation and file references', function (bool $peer, array $permissions, bool $allowed): void {
        $user = $peer ? User::factory()->create(['admin' => false]) : User::query()->firstOrFail();
        $user->admin = false;
        $user->save();
        $volume = Volume::findOrFail($this->folder->volumeId);
        app(UserPermissions::class)->saveUserPermissions($user->id, array_map(
            static fn (string $permission): string => "$permission:$volume->uid",
            ['viewAssets', ...($peer ? ['viewPeerAssets'] : []), ...$permissions],
        ));
        actingAs($user);
        app(Request::class)->setUserResolver(static fn (): User => $user);
        Http::fake(['https://files.example.test/replacement' => Http::response('abc')]);
        $assets = app(Assets::class);
        $file = ['download_url' => 'https://files.example.test/replacement', 'file_id' => 'replacement', 'file_name' => 'replacement.txt'];

        if ($allowed) {
            $upload = $assets->prepareUpload('replacement.txt', 3, assetId: $this->asset->id)['upload'];
            expect($assets->replace($this->asset->id, file: $file)['asset']['id'])->toBe($this->asset->id);
            expect(Storage::disk('upload-destination')->get(Asset::findOne($this->asset->id)->getPath()))->toBe('abc');
            app(Uploads::class)->cancel(app(Request::class), $upload['id']);
        } else {
            expect(fn () => $assets->prepareUpload('replacement.txt', 3, assetId: $this->asset->id))->toThrow(ToolCallException::class)
                ->and(fn () => $assets->replace($this->asset->id, file: $file))->toThrow(ToolCallException::class);

            Http::assertNothingSent();
            expect(Storage::disk('upload-destination')->get($this->asset->getPath()))->toBe('Original content');
        }

        expect(UploadSession::count())->toBe(0);
    })->with([
        'save and upload permissions do not allow replacement' => [false, ['saveAssets'], false],
        'replacement does not require save or upload permissions' => [false, ['replaceFiles'], true],
        'peer replacement requires peer permission' => [true, ['replaceFiles'], false],
        'peer replacement is allowed with both permissions' => [true, ['replaceFiles', 'replacePeerFiles'], true],
    ]);

    it('turns HTTP replacement validation failures into tool errors and releases the downloaded file', function (): void {
        Http::fake(['https://files.example.test/replacement' => Http::response('abc')]);
        $temporaryFiles = File::files(Path::temp());
        Event::listen(AssetReplacing::class, static function (AssetReplacing $event): void {
            $event->filename = 'replacement.php';
        });

        expect(fn () => app(Assets::class)->replace(
            $this->asset->id,
            file: ['download_url' => 'https://files.example.test/replacement', 'file_id' => 'replacement', 'file_name' => 'replacement.txt'],
        ))->toThrow(ToolCallException::class, 'not an allowed file extension');

        expect(File::files(Path::temp()))->toEqual($temporaryFiles)
            ->and(Storage::disk('upload-destination')->get($this->asset->getPath()))->toBe('Original content');
    });
});
