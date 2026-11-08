<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage;
use CraftCms\Cms\Asset\Assets as AssetsService;
use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FilenameConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Mcp\Capabilities\Assets;
use CraftCms\Cms\Mcp\StdioTransport;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $created = McpRequest::send($this, 'tools/call', [
        'name' => 'assets_create',
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
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    Http::fake(['https://files.example.test/*' => Http::response('Downloaded content')]);
    $temporaryFiles = File::files(Path::temp());

    $create = collect(McpRequest::tools($this))->firstWhere('name', 'assets_create');

    expect($create['_meta']['openai/fileParams'])->toBe(['file'])
        ->and($create['inputSchema']['properties']['file']['properties'])->toHaveKeys(['download_url', 'data', 'path', 'file_id', 'mime_type', 'file_name'])
        ->and($create['inputSchema']['properties']['file'])->not->toHaveKey('required');

    $created = McpRequest::send($this, 'tools/call', [
        'name' => 'assets_create',
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

it('sets and clears an image’s focal point through elements_update', function (): void {
    Http::fake(['https://files.example.test/*' => Http::response(File::get(dirname(__DIR__, 2).'/_data/assets/files/google.png'))]);
    $id = app(Assets::class)->create(
        file: ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_image', 'file_name' => 'image.png'],
        volumeId: $this->folder->volumeId,
    )['asset']['id'];
    config()->set('passport.public_key', openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $update = fn (mixed $focalPoint) => McpRequest::send($this, 'tools/call', [
        'name' => 'elements_update',
        'arguments' => ['type' => 'assets', 'id' => $id, 'attributes' => ['focalPoint' => $focalPoint]],
    ])->assertOk();

    $update('0.25;0.8')
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.hasFocalPoint', true);

    expect(Asset::findOne($id)->getFocalPoint())->toEqual(['x' => 0.25, 'y' => 0.8]);

    $update('1.5;0.5')->assertJsonPath('result.isError', true);
    $update(null)->assertJsonPath('result.isError', false);

    expect(Asset::findOne($id)->getHasFocalPoint())->toBeFalse();
});

it('sets an asset’s uploader through elements_update', function (bool $peer, bool $allowed): void {
    Http::fake(['https://files.example.test/*' => Http::response('abc')]);
    $user = User::factory()->create(['admin' => false]);
    $volume = Volume::findOrFail($this->folder->volumeId);
    app(UserPermissions::class)->saveUserPermissions($user->id, [
        'accessCp',
        ...array_map(
            static fn (string $permission): string => "$permission:$volume->uid",
            ['viewAssets', 'saveAssets', ...($peer ? ['viewPeerAssets', 'savePeerAssets'] : [])],
        ),
    ]);
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);
    $id = app(Assets::class)->create(
        file: ['download_url' => 'https://files.example.test/download', 'file_id' => 'file_text', 'file_name' => 'file.txt'],
        volumeId: $this->folder->volumeId,
    )['asset']['id'];
    $other = User::factory()->create();
    config()->set('passport.public_key', openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key']);
    Passport::actingAs($user, ['mcp:admin'], 'craft-mcp');
    $update = fn (mixed $uploaderId) => McpRequest::send($this, 'tools/call', [
        'name' => 'elements_update',
        'arguments' => ['type' => 'assets', 'id' => $id, 'attributes' => ['uploaderId' => $uploaderId]],
    ])->assertOk();

    $update($other->id)->assertJsonPath('result.isError', ! $allowed);

    expect(Asset::findOne($id)->uploaderId)->toBe($allowed ? $other->id : $user->id);

    if ($allowed) {
        // Within the integer column's range: PostgreSQL rejects larger IDs outright.
        $update(User::query()->max('id') + 1)->assertJsonPath('result.isError', true);
        $update(null)->assertJsonPath('result.isError', false);

        expect(Asset::findOne($id)->uploaderId)->toBeNull();
    }
})->with([
    'reassigning requires peer permissions' => [false, false],
    'peer permissions allow reassigning' => [true, true],
]);

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
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp']);
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

it('creates assets from base64 data and redirected download URLs without file IDs', function (array $reference, ?string $filename): void {
    Http::fake([
        'https://files.example.test/imported.txt' => Http::response('', 302, ['Location' => '/hop']),
        'https://files.example.test/hop' => Http::response('', 301, ['Location' => 'https://cdn.example.test/file#fragment']),
        'https://cdn.example.test/file' => Http::response('Decoded content'),
    ]);
    $temporaryFiles = File::files(Path::temp());

    $created = app(Assets::class)->create(file: $reference, folderId: $this->folder->id, filename: $filename)['asset'];
    $asset = Asset::findOne($created['id']);

    expect($asset->getFilename())->toBe('imported.txt')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('Decoded content')
        ->and(File::files(Path::temp()))->toEqual($temporaryFiles);
})->with([
    'base64 data' => [['data' => base64_encode('Decoded content'), 'file_name' => 'imported.txt'], null],
    'base64 data URL with line breaks' => [['data' => 'data:text/plain;base64,'.chunk_split(base64_encode('Decoded content'), 8)], 'imported.txt'],
    'download URL basename and redirects' => [['download_url' => 'https://files.example.test/imported.txt'], null],
]);

it('rejects invalid file references without saving an asset', function (array $reference, string $message): void {
    Cms::config()->maxUploadFileSize = 16;
    Http::fake([
        'https://files.example.test/loop' => Http::response('', 302, ['Location' => 'https://files.example.test/loop']),
        'https://files.example.test/downgrade' => Http::response('', 302, ['Location' => 'http://files.example.test/file.txt']),
    ]);

    expect(fn () => app(Assets::class)->create(file: $reference, folderId: $this->folder->id, filename: 'imported.txt'))
        ->toThrow(ToolCallException::class, $message);

    expect(Asset::find()->count())->toBe(0)
        ->and(Storage::disk('upload-destination')->allFiles())->toBe([]);
})->with([
    'no source' => [['file_id' => 'file_example'], 'exactly one of'],
    'two sources' => [['download_url' => 'https://files.example.test/file.txt', 'data' => 'YWJj'], 'exactly one of'],
    'invalid base64' => [['data' => 'not base64!'], 'must be base64-encoded'],
    'oversized base64' => [['data' => base64_encode(str_repeat('a', 17))], 'exceeds the maximum upload size'],
    'too many redirects' => [['download_url' => 'https://files.example.test/loop'], 'redirected more than 3 times'],
    'redirect from HTTPS to HTTP' => [['download_url' => 'https://files.example.test/downgrade'], 'does not use HTTPS'],
    'local path over HTTP' => [['path' => __FILE__], 'only supported by the stdio server'],
]);

it('creates assets from local project files through the stdio server', function (): void {
    $directory = base_path('mcp-upload-test');
    File::ensureDirectoryExists($directory);
    File::put("$directory/local.txt", 'Local content');
    $input = fopen('php://memory', 'r+');
    fwrite($input, implode('', array_map(static fn (array $message): string => json_encode($message).PHP_EOL, [
        ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
        ]],
        ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => ['name' => 'assets_create', 'arguments' => [
            'file' => ['path' => "$directory/local.txt"],
            'folderId' => $this->folder->id,
        ]]],
    ])));
    rewind($input);
    $outputPath = tempnam(sys_get_temp_dir(), 'mcp-stdio');
    app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, $input, fopen($outputPath, 'w')));

    try {
        $this->artisan('craft:mcp:serve', ['--user' => User::query()->firstOrFail()->username])->assertSuccessful();
    } finally {
        File::deleteDirectory($directory);
    }

    $response = collect(file($outputPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
        ->map(static fn (string $line): array => json_decode($line, true))
        ->firstWhere('id', 'create');
    unlink($outputPath);
    $asset = Asset::findOne($response['result']['structuredContent']['asset']['id'] ?? null);

    expect($response['result']['isError'] ?? null)->toBeFalse()
        ->and($asset->getFilename())->toBe('local.txt')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('Local content');
});

it('prepares uploads over stdio that clients transfer with the upload’s own credential', function (): void {
    $serve = function (string $tool, array $arguments): array {
        $input = fopen('php://memory', 'r+');
        fwrite($input, implode('', array_map(static fn (array $message): string => json_encode($message).PHP_EOL, [
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'call', 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]],
        ])));
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'mcp-stdio');
        app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, $input, fopen($outputPath, 'w')));
        $this->artisan('craft:mcp:serve', ['--user' => User::query()->firstOrFail()->username])->assertSuccessful();
        $response = collect(file($outputPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(static fn (string $line): array => json_decode($line, true))
            ->firstWhere('id', 'call');
        unlink($outputPath);

        return $response['result'];
    };

    $upload = $serve('assets_upload_prepare', ['filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id])['structuredContent']['upload'];
    app(Factory::class)->forgetGuards();
    $transfer = fn (?string $token) => $this->call('PATCH', $upload['urls']['transfer'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0',
        'HTTP_UPLOAD_OFFSET' => '0',
        ...($token === null ? [] : ['HTTP_AUTHORIZATION' => "Bearer $token"]),
    ], content: 'abc');

    $transfer(null)->assertUnauthorized();
    $transfer(hash('sha256', 'guess'))->assertUnauthorized();
    $transfer($upload['authorization']['token'])->assertNoContent()->assertHeader('Upload-Offset', '3');

    $created = $serve('assets_create', ['uploadId' => $upload['id']]);
    $asset = Asset::findOne($created['structuredContent']['asset']['id'] ?? null);

    expect(parse_url($upload['urls']['transfer'], PHP_URL_PATH))->toBe('/'.Cms::config()->actionTrigger."/mcp/upload-sessions/{$upload['id']}/transfer")
        ->and($upload['transport']['options']['url'])->toBe($upload['urls']['transfer'])
        ->and($asset->getFilename())->toBe('example.txt')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc');
});

it('requires an HTTP site URL to prepare uploads over stdio', function (string $url): void {
    config()->set('app.url', $url);
    Context::addHidden(StdioTransport::ContextStdio, true);

    expect(fn () => app(Assets::class)->prepareUpload('example.txt', 3, folderId: $this->folder->id))
        ->toThrow(ToolCallException::class, 'Set the APP_URL environment variable')
        ->and(UploadSession::count())->toBe(0);
})->with(['empty' => '', 'relative' => '/craft', 'no host' => 'https://', 'file' => 'file:///tmp/craft']);

it('rejects stdio upload credentials once their session expires', function (): void {
    Context::addHidden(StdioTransport::ContextStdio, true);
    $upload = app(Assets::class)->prepareUpload('example.txt', 3, folderId: $this->folder->id)['upload'];
    Context::forgetHidden(StdioTransport::ContextStdio);
    app(Factory::class)->forgetGuards();
    $this->travel(Cms::config()->uploadSessionDuration + 1)->seconds();

    $this->call('DELETE', $upload['urls']['transfer'], server: [
        'HTTP_TUS_RESUMABLE' => '1.0.0',
        'HTTP_AUTHORIZATION' => "Bearer {$upload['authorization']['token']}",
    ])->assertStatus(410);
});

it('rejects local paths where volumes could not be', function (string $path): void {
    File::put($path, 'Local content');
    Context::addHidden(StdioTransport::ContextStdio, true);

    try {
        expect(fn () => app(Assets::class)->create(file: ['path' => $path], folderId: $this->folder->id))
            ->toThrow(ToolCallException::class, 'within the Craft project, outside of system directories');
    } finally {
        File::delete($path);
    }

    expect(Asset::find()->count())->toBe(0);
})->with([
    'outside the project' => fn (): string => sys_get_temp_dir().'/mcp-upload-test.txt',
    'project root' => fn (): string => base_path('mcp-upload-test.txt'),
    'config directory' => fn (): string => config_path('mcp-upload-test.txt'),
]);

describe('field uploads', function (): void {
    beforeEach(function (): void {
        config()->set('filesystems.disks.temp-assets', ['driver' => 'local', 'root' => storage_path('framework/testing/temp-assets')]);
        Storage::fake('temp-assets');
        Cms::config()->tempAssetUploadDisk = 'temp-assets';

        $caption = Field::factory()->create(['handle' => 'caption', 'type' => PlainText::class]);
        $this->volume = Volume::factory()->create([
            'fs' => 'upload-destination',
            'fieldLayoutId' => FieldLayout::factory()->forField($caption)->create(['type' => Asset::class])->id,
        ]);
        VolumeFolder::factory()->create(['volumeId' => $this->volume->id, 'path' => '']);
        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $this->assetsField = fn (array $settings = []) => EntryModel::factory()
            ->withField('attachment', AssetsField::class, [
                'defaultUploadLocationSource' => "volume:{$this->volume->uid}",
                'defaultUploadLocationSubpath' => 'field-upload-test',
                ...$settings,
            ])
            ->createElementWithFields(['title' => 'Upload target']);
        $this->filenameCondition = [
            'class' => AssetCondition::class,
            'conditionRules' => [['class' => FilenameConditionRule::class, 'operator' => '=', 'value' => 'notes.txt']],
        ];
    });

    it('stages uploads for fields with selection conditions, matching their final location and keeping field values', function (string $source): void {
        $result = ($this->assetsField)(['selectionCondition' => $this->filenameCondition]);
        $fieldId = $result->fields->get('attachment')->id;
        $assets = app(Assets::class);

        if ($source === 'upload') {
            $upload = $assets->prepareUpload('notes.txt', 3, fieldId: $fieldId, elementId: $result->element->id, siteId: $result->element->siteId)['upload'];
            $request = Request::create($upload['urls']['transfer'], 'PATCH', server: [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_TUS_RESUMABLE' => '1.0.0',
                'HTTP_UPLOAD_OFFSET' => 0,
            ], content: 'abc');
            $request->setUserResolver(static fn (): User => User::query()->firstOrFail());
            app(Uploads::class)->transfer($request, $upload['id']);
            $created = $assets->create($upload['id'], fields: ['caption' => 'Hello'])['asset'];
        } else {
            $created = $assets->create(
                fields: ['caption' => 'Hello'],
                file: ['data' => base64_encode('abc'), 'file_name' => 'notes.txt'],
                fieldId: $fieldId,
                elementId: $result->element->id,
            )['asset'];
        }

        $asset = Asset::findOne($created['id']);

        expect($asset->getFilename())->toBe('notes.txt')
            ->and($asset->getFolder()->path)->toBe('field-upload-test/')
            ->and($asset->getFieldValue('caption'))->toBe('Hello')
            ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc')
            ->and(Storage::disk('temp-assets')->allFiles())->toBe([]);
    })->with(['file reference', 'upload']);

    it('rejects files that do not match the field’s selection condition', function (): void {
        $result = ($this->assetsField)(['selectionCondition' => $this->filenameCondition]);

        expect(fn () => app(Assets::class)->create(
            file: ['data' => base64_encode('abc'), 'file_name' => 'other.txt'],
            fieldId: $result->fields->get('attachment')->id,
            elementId: $result->element->id,
        ))->toThrow(ToolCallException::class, 'other.txt isn’t selectable for this field.');

        expect(Asset::find()->count())->toBe(0)
            ->and(Storage::disk('upload-destination')->allFiles())->toBe([])
            ->and(Storage::disk('temp-assets')->allFiles())->toBe([]);
    });

    it('checks the field’s volume permissions before resolving its upload location', function (array $permissions, bool $allowed): void {
        $result = ($this->assetsField)(['defaultUploadLocationSubpath' => '{id}']);
        $user = User::factory()->create(['admin' => false]);
        app(UserPermissions::class)->saveUserPermissions($user->id, array_map(
            fn (string $permission): string => "$permission:{$this->volume->uid}",
            $permissions,
        ));
        actingAs($user);
        app(Request::class)->setUserResolver(static fn (): User => $user);
        $create = fn (): array => app(Assets::class)->create(
            file: ['data' => base64_encode('abc'), 'file_name' => 'notes.txt'],
            fieldId: $result->fields->get('attachment')->id,
        );

        if (! $allowed) {
            expect($create)->toThrow(ToolCallException::class, 'not authorized');

            return;
        }

        $asset = Asset::findOne($create()['asset']['id']);

        expect($asset->getVolumeId())->toBeNull()
            ->and($asset->folderId)->toBe(app(AssetsService::class)->getUserTemporaryUploadFolder()->id)
            ->and(Storage::disk('temp-assets')->get($asset->getPath()))->toBe('abc');
    })->with([
        'without permission to save assets in the field’s volume' => [['viewAssets'], false],
        'falling back to the user’s temporary upload folder' => [['viewAssets', 'saveAssets'], true],
    ]);

    it('rejects field uploads combined with other destinations', function (): void {
        $result = ($this->assetsField)();
        $file = ['data' => base64_encode('abc'), 'file_name' => 'notes.txt'];

        expect(fn () => app(Assets::class)->create(file: $file, folderId: $this->folder->id, fieldId: $result->fields->get('attachment')->id))
            ->toThrow(ToolCallException::class, 'exactly one of: folderId, volumeId, fieldId')
            ->and(fn () => app(Assets::class)->create(file: $file, folderId: $this->folder->id, elementId: $result->element->id))
            ->toThrow(ToolCallException::class, 'elementId is only used with fieldId');
    });
});

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
        Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
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
                'name' => 'assets_upload_prepare',
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

            expect(collect(McpRequest::tools($this))->firstWhere('name', 'assets_replace')['_meta']['openai/fileParams'])->toBe(['file']);
        }

        McpRequest::send($this, 'tools/call', [
            'name' => 'assets_replace',
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
            ->and(fn () => $assets->replace($this->asset->id, file: ['data' => base64_encode('abc')]))->toThrow(ToolCallException::class, 'Provide filename');

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

describe('assets_analyze', function () {
    beforeEach(function () {
        Queue::fake();
        $this->image = AssetModel::factory()->createElement(['volumeId' => $this->folder->volumeId, 'folderId' => $this->folder->id, 'kind' => 'image', 'filename' => 'photo.png']);
        $this->document = AssetModel::factory()->createElement(['volumeId' => $this->folder->volumeId, 'folderId' => $this->folder->id, 'kind' => 'pdf', 'filename' => 'doc.pdf']);
    });

    $setUpAi = function (): void {
        config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
        app()->instance(Settings::class, new Settings(['defaults' => ['text' => ['provider' => 'openai']]]));
    };

    it('queues the images that have something for the tasks to fill in', function () use ($setUpAi): void {
        $setUpAi();

        expect(app(Assets::class)->analyze([$this->image->id, $this->document->id], ['craft:altText']))
            ->toBe(['queued' => [$this->image->id], 'skipped' => [$this->document->id]]);
        Queue::assertPushed(AnalyzeImage::class, fn (AnalyzeImage $job): bool => $job->assetId === $this->image->id && $job->tasks === ['craft:altText']);
    });

    it('says when no image analysis tasks are set up, or a task isn’t one', function () use ($setUpAi): void {
        expect(fn () => app(Assets::class)->analyze([$this->image->id]))
            ->toThrow(ToolCallException::class, 'No image analysis AI tasks are set up.');

        $setUpAi();

        expect(fn () => app(Assets::class)->analyze([$this->image->id], ['seo:metaDescription']))
            ->toThrow(ToolCallException::class, 'Unknown image analysis tasks: seo:metaDescription.');
        Queue::assertNothingPushed();
    });
});
