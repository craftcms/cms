<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetFileHandling;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Filesystem\Contracts\ReceivesTusUploads;
use CraftCms\Cms\Filesystem\Contracts\SignsS3Uploads;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploaders;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Http\Controllers\Assets\UploadSessionController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    config()->set('filesystems.disks.upload-parts', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-parts')]);
    config()->set('filesystems.disks.upload-destination', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-destination')]);
    Storage::fake('upload-parts');
    Storage::fake('upload-destination');
    Cms::config()->tempAssetUploadFs = 'disk:upload-parts';
    Cms::config()->uploadChunkSize = 3;

    $this->volume = Volume::factory()->create(['fs' => 'disk:upload-destination']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id, 'path' => '']);
});

it('requires authentication before creating an upload', function () {
    auth()->logout();

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertUnauthorized();

    expect(UploadSession::count())->toBe(0);
    Storage::disk('upload-parts')->assertDirectoryEmpty('/');
});

it('authorizes the destination before accepting bytes', function () {
    Gate::partialMock()->shouldReceive('authorize')->andThrow(new AuthorizationException);

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertForbidden();

    expect(UploadSession::count())->toBe(0);
});

it('negotiates tus, resumes short requests, and completes idempotently', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->assertJsonPath('chunkSize', 3)->assertJsonPath('transport.type', 'tus')->json();

    $url = $session['transport']['options']['url'];

    $this->options($url)->assertNoContent()->assertHeader('Tus-Version', '1.0.0')
        ->assertHeader('Tus-Extension', 'termination,expiration');
    $this->head($url)->assertStatus(412)->assertHeader('Tus-Resumable', '1.0.0');
    $this->head($url, ['Tus-Resumable' => '1.0.0'])->assertOk()
        ->assertHeader('Upload-Offset', 0)->assertHeader('Upload-Length', 6);

    foreach ([[0, 'ab'], [2, 'cde'], [5, 'f']] as [$offset, $bytes]) {
        $this->call('PATCH', $session['transport']['options']['url'], server: [
            'CONTENT_TYPE' => 'application/offset+octet-stream',
            'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => $offset,
        ], content: $bytes)->assertNoContent()->assertHeader('Tus-Resumable', '1.0.0')
            ->assertHeader('Upload-Offset', $offset + strlen($bytes));
    }

    $this->call('PATCH', $session['transport']['options']['url'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0,
    ], content: 'abc')->assertConflict()->assertHeader('Upload-Offset', 6);

    $this->getJson($session['urls']['status'])->assertExactJson(['uploaded' => true]);
    Storage::disk('upload-parts')->put("upload-sessions/{$session['id']}/file", 'partial');
    $completed = postJson($session['urls']['complete'])->assertOk()->assertJsonMissingPath('conflictingAssetId')->json();
    $asset = Asset::findOne($completed['assetId']);

    expect($asset->getFilename())->toBe('example.txt')
        ->and($asset->size)->toBe(6);
    expect(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abcdef');
    Storage::disk('upload-parts')->assertDirectoryEmpty('upload-sessions');

    postJson($session['urls']['complete'])
        ->assertOk()->assertJsonPath('assetId', $asset->id);
    expect(Asset::find()->folderId($this->folder->id)->count())->toBe(1);
});

it('rejects oversized tus requests and incomplete files', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0,
    ], content: 'toolong')->assertStatus(413);
    postJson($session['urls']['complete'])->assertUnprocessable();

    expect(Asset::find()->folderId($this->folder->id)->count())->toBe(0);
    Storage::disk('upload-parts')->assertMissing("upload-sessions/{$session['id']}/parts/0");
});

it('does not accept another users upload session', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    auth()->logout();

    $this->getJson($session['urls']['status'])->assertNotFound();
    postJson($session['urls']['complete'])->assertNotFound();
    $this->deleteJson($session['urls']['cancel'])->assertNotFound();
    expect(UploadSession::count())->toBe(1);
});

it('rechecks permissions before resuming an upload', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    Gate::partialMock()->shouldReceive('authorize')->andThrow(new AuthorizationException);

    $this->getJson($session['urls']['status'])->assertForbidden();
    $this->deleteJson($session['urls']['cancel'])->assertNoContent();
});

it('allows a five gib asset when configured regardless of PHP request limits', function () {
    Cms::config()->maxUploadFileSize = 5368709120;
    Cms::config()->uploadChunkSize = 2097152;

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'archive.zip', 'size' => 5368709120, 'folderId' => $this->folder->id,
    ])->assertCreated()->assertJsonPath('partCount', 2560);

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'archive.zip', 'size' => 5368709121, 'folderId' => $this->folder->id,
    ])->assertUnprocessable();
});

it('expires abandoned sessions but preserves active uploads', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    $this->travel(23)->hours();
    $this->getJson($session['urls']['status'])->assertOk();
    $this->travel(2)->hours();
    expect(app(Uploads::class)->cleanupExpired())->toBe(['removed' => 0, 'failed' => 0]);

    $this->travel(23)->hours();
    $this->getJson($session['urls']['status'])->assertGone();
    expect(app(Uploads::class)->cleanupExpired())->toBe(['removed' => 1, 'failed' => 0]);
    expect(UploadSession::count())->toBe(0);
});

it('binds guest uploads to application-defined destinations and limits', function () {
    auth()->logout();
    $authorize = fn (Request $request, array $file): bool => $file['folderId'] === $this->folder->id
        && $file['size'] <= 3
        && $file['filename'] === 'proof of address.txt'
        && ($file['context']['form'] ?? null) === 'submission';
    app(AssetUploads::class)->allowGuestUploadsUsing($authorize);

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'proof of address.txt', 'size' => 4, 'folderId' => $this->folder->id, 'context' => ['form' => 'submission'],
    ])->assertForbidden();

    postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'proof of address.txt', 'size' => 3, 'folderId' => 99999, 'context' => ['form' => 'submission'],
    ])->assertForbidden();

    expect(UploadSession::count())->toBe(0);

    $response = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'proof of address.txt', 'size' => 3, 'folderId' => $this->folder->id, 'context' => ['form' => 'submission'],
    ])->assertCreated();
    $session = $response->json();
    $this->withCredentials();
    $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());

    $storedSession = UploadSession::findOrFail($session['id']);
    expect($storedSession->parameters['folderId'])->toBe($this->folder->id);
    $storedSession->update(['parameters' => array_reverse($storedSession->parameters, preserve_keys: true)]);
    app(AssetUploads::class)->allowGuestUploadsUsing(fn (): bool => false);
    $this->getJson($session['urls']['status'])->assertForbidden();
    postJson($session['urls']['complete'])->assertForbidden();
    Storage::disk('upload-destination')->assertDirectoryEmpty('/');

    app(AssetUploads::class)->allowGuestUploadsUsing($authorize);
    $this->call('PATCH', $session['transport']['options']['url'], cookies: $this->prepareCookiesForRequest(), server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0], content: 'abc')->assertNoContent();
    $completed = postJson($session['urls']['complete'])->assertOk()->json();

    $asset = Asset::findOne($completed['assetId']);
    expect($asset->folderId)->toBe($this->folder->id)
        ->and($asset->filename)->toBe('proof-of-address.txt')
        ->and(Storage::disk('upload-destination')->get($asset->getPath()))->toBe('abc');
});

it('preserves image dimensions through chunk assembly and asset processing', function () {
    $image = UploadedFile::fake()->image('photo.png', 13, 17);
    $bytes = file_get_contents($image->getRealPath());
    Cms::config()->uploadChunkSize = 1048576;
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'photo.png', 'size' => strlen($bytes), 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0], content: $bytes)->assertNoContent();
    $completed = postJson($session['urls']['complete'])->assertOk()->json();

    $asset = Asset::findOne($completed['assetId']);
    expect($asset->getWidth())->toBe(13)->and($asset->getHeight())->toBe(17)
        ->and($asset->getMimeType())->toBe('image/png');
});

it('resolves a fields dynamic upload folder from its element context', function () {
    $result = Entry::factory()
        ->withField('attachment', Assets::class, [
            'defaultUploadLocationSource' => "volume:{$this->volume->uid}",
            'defaultUploadLocationSubpath' => '{uid}',
        ])->createElementWithFields(['title' => 'Upload target']);

    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3,
        'fieldId' => $result->fields->get('attachment')->id,
        'elementId' => $result->element->id, 'siteId' => $result->element->siteId,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0], content: 'abc')->assertNoContent();
    $completed = postJson($session['urls']['complete'])->assertOk()->json();

    expect(Asset::findOne($completed['assetId'])->getPath())->toBe("{$result->element->uid}/example.txt");
});

it('keeps the staged bytes when required processing fails and retries completion', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0], content: 'abc')->assertNoContent();
    Event::listen(AssetFileHandling::class, function () {
        throw new RuntimeException('Required processing failed.');
    });
    postJson($session['urls']['complete'])->assertServerError();
    Storage::disk('upload-parts')->assertExists("upload-sessions/{$session['id']}/file");

    Event::forget(AssetFileHandling::class);
    postJson($session['urls']['complete'])->assertOk();
    expect(Asset::find()->folderId($this->folder->id)->count())->toBe(1);
});

it('replaces the existing asset without creating a second asset', function (string $subpath) {
    config()->set('filesystems.disks.upload-destination.root', Storage::disk('upload-destination')->path(''));
    $this->volume = Volume::factory()->create(['fs' => 'disk:upload-destination', 'subpath' => $subpath]);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id, 'path' => '']);

    $upload = function (string $bytes, array $parameters): array {
        $session = postJson(action([UploadSessionController::class, 'store']), [
            'filename' => 'example.txt', 'size' => strlen($bytes), ...$parameters,
        ])->assertCreated()->json();
        foreach (str_split($bytes, 3) as $index => $chunk) {
            $this->call('PATCH', $session['transport']['options']['url'], server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => $index * 3], content: $chunk)->assertNoContent();
        }

        return postJson($session['urls']['complete'])->assertOk()->json();
    };

    $original = $upload('abc', ['folderId' => $this->folder->id]);
    $replacement = $upload('defghi', ['operation' => 'replace', 'assetId' => $original['assetId']]);
    $asset = Asset::findOne($original['assetId']);

    expect($replacement['assetId'])->toBe($original['assetId'])
        ->and($asset->size)->toBe(6)
        ->and(Storage::disk('upload-destination')->get($subpath.$asset->getPath()))->toBe('defghi')
        ->and(Asset::find()->folderId($this->folder->id)->count())->toBe(1);
})->with([
    'volume root' => [''],
    'volume subpath' => ['uploads/'],
]);

it('rejects uploaded files that do not match the fields selection condition', function () {
    $result = Entry::factory()
        ->withField('attachment', Assets::class, [
            'defaultUploadLocationSource' => "volume:{$this->volume->uid}",
            'selectionCondition' => [
                'class' => AssetCondition::class,
                'conditionRules' => [[
                    'class' => FileTypeConditionRule::class,
                    'values' => ['image'],
                ]],
            ],
        ])->createElementWithFields([]);

    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'attachment.txt', 'size' => 3,
        'fieldId' => $result->fields->get('attachment')->id,
        'elementId' => $result->element->id,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: ['CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0], content: 'abc')->assertNoContent();

    postJson($session['urls']['complete'])->assertBadRequest()
        ->assertJsonPath('message', 'attachment.txt isn’t selectable for this field.');
    expect(Asset::find()->count())->toBe(0);
});

it('rejects malformed tus patches without advancing the offset', function (array $headers, int $status) {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    $this->call('PATCH', $session['transport']['options']['url'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream', 'HTTP_TUS_RESUMABLE' => '1.0.0',
        ...$headers,
    ], content: 'abc')->assertStatus($status)->assertHeader('Tus-Resumable', '1.0.0');

    expect(UploadSession::findOrFail($session['id'])->state['offset'])->toBe(0);
    Storage::disk('upload-parts')->assertMissing("upload-sessions/{$session['id']}/parts/0");
})->with([
    'missing offset' => [[], 400],
    'negative offset' => [['HTTP_UPLOAD_OFFSET' => '-1'], 400],
    'fractional offset' => [['HTTP_UPLOAD_OFFSET' => '1.5'], 400],
    'offset mismatch' => [['HTTP_UPLOAD_OFFSET' => '3'], 409],
    'media type' => [['HTTP_UPLOAD_OFFSET' => '0', 'CONTENT_TYPE' => 'application/octet-stream'], 415],
]);

it('terminates tus uploads and tolerates repeated cleanup', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $this->call('PATCH', $session['transport']['options']['url'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0,
    ], content: 'abc')->assertNoContent();

    $this->delete($session['transport']['options']['url'], headers: ['Tus-Resumable' => '1.0.0'])->assertNoContent();
    $this->deleteJson($session['urls']['cancel'])->assertNoContent();
    $this->head($session['transport']['options']['url'], ['Tus-Resumable' => '1.0.0'])->assertNotFound();
    expect(UploadSession::count())->toBe(0);
    Storage::disk('upload-parts')->assertDirectoryEmpty('upload-sessions');
});

it('binds custom S3 signatures to the authorized sessions key and multipart id', function () {
    $uploader = Mockery::mock(SignsS3Uploads::class);
    $uploader->shouldReceive('clientConfig')->andReturnUsing(fn (UploadSession $session) => [
        'type' => 's3', 'options' => ['key' => "custom/{$session->id}", 'uploadId' => 'multipart-id'],
    ]);
    $uploader->shouldReceive('start')->andReturnUsing(function (UploadSession $session) {
        $session->chunkSize = 8388608;
        $session->state = ['uploadId' => 'multipart-id'];
    });
    $uploader->shouldReceive('sign')->once()->withArgs(fn (UploadSession $session, string $method, ?int $part) => $method === 'PUT' && $part === 1)
        ->andReturn(['url' => 'https://storage.example/signed']);
    app(Uploaders::class)->extend('s3-test', fn () => $uploader);
    Cms::config()->uploader = 's3-test';
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->assertJsonPath('transport.type', 's3')->json();
    $request = ['method' => 'PUT', 'partNumber' => 1, 'key' => $session['transport']['options']['key'], 'uploadId' => 'multipart-id'];

    postJson($session['urls']['sign'], [...$request, 'key' => 'another/file'])->assertNotFound();
    postJson($session['urls']['sign'], [...$request, 'uploadId' => 'another-upload'])->assertNotFound();
    postJson($session['urls']['sign'], [...$request, 'method' => 'HEAD'])->assertUnprocessable();
    postJson($session['urls']['sign'], $request)->assertOk()->assertJsonPath('url', 'https://storage.example/signed');

    Gate::partialMock()->shouldReceive('authorize')->andThrow(new AuthorizationException);
    postJson($session['urls']['sign'], $request)->assertForbidden();
});

it('accepts an independent tus receiver and reads its offset through the contract', function () {
    $uploader = Mockery::mock(ReceivesTusUploads::class);
    $uploader->shouldReceive('start')->andReturnUsing(function (UploadSession $session) {
        $session->chunkSize = 3;
        $session->state = ['received' => 0];
    });
    $uploader->shouldReceive('clientConfig')->andReturnUsing(fn (UploadSession $session) => [
        'type' => 'custom-tus',
        'options' => ['url' => route('craft.actions.craft.uploads.tus', ['upload' => $session->id])],
    ]);
    $uploader->shouldReceive('offset')->andReturnUsing(fn (UploadSession $session) => $session->state['received']);
    $uploader->shouldReceive('receive')->once()->andReturnUsing(function (UploadSession $session, int $offset, mixed $stream) {
        expect($offset)->toBe(0)->and(stream_get_contents($stream))->toBe('abc');
        $session->state = ['received' => 3];
    });
    app(Uploaders::class)->extend('custom-tus', fn () => $uploader);
    Cms::config()->uploader = 'custom-tus';
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->assertJsonPath('transport.type', 'custom-tus')->json();
    $url = $session['transport']['options']['url'];

    $this->call('PATCH', $url, server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0,
    ], content: 'abc')->assertNoContent()->assertHeader('Upload-Offset', 3);
    $this->head($url, ['Tus-Resumable' => '1.0.0'])->assertOk()->assertHeader('Upload-Offset', 3);
});
