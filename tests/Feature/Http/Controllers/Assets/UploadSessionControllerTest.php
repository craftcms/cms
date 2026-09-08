<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetFileHandling;
use CraftCms\Cms\Asset\Models\UploadSession;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets;
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

it('assembles retried parts once and completes idempotently', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->assertJsonPath('chunkSize', 3)->json();

    foreach ([[1, 'abc'], [2, 'def'], [1, 'abc']] as [$part, $bytes]) {
        $request = postJson($session['urls']['part'], ['part' => $part])->assertOk()->json();
        $this->call('POST', $request['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: $bytes)
            ->assertNoContent();
    }

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

it('rejects incorrect part sizes and incomplete files', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 6, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $request = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();

    $this->call('POST', $request['url'], server: ['CONTENT_TYPE' => 'application/octet-stream', 'HTTP_ACCEPT' => 'application/json'], content: 'toolong')
        ->assertUnprocessable();
    postJson($session['urls']['part'], ['part' => 3])->assertUnprocessable();
    postJson($session['urls']['complete'])->assertUnprocessable();

    expect(Asset::find()->folderId($this->folder->id)->count())->toBe(0);
    Storage::disk('upload-parts')->assertMissing("upload-sessions/{$session['id']}/parts/1");
});

it('does not accept another users upload session', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    auth()->logout();

    postJson($session['urls']['part'], ['part' => 1])->assertNotFound();
    postJson($session['urls']['complete'])->assertNotFound();
    $this->deleteJson($session['urls']['cancel'])->assertNotFound();
    expect(UploadSession::count())->toBe(1);
});

it('rechecks permissions before issuing another part request', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();

    Gate::partialMock()->shouldReceive('authorize')->andThrow(new AuthorizationException);

    postJson($session['urls']['part'], ['part' => 1])->assertForbidden();
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
    postJson($session['urls']['part'], ['part' => 1])->assertOk();
    $this->travel(2)->hours();
    expect(app(AssetUploads::class)->cleanupExpired())->toBe(['removed' => 0, 'failed' => 0]);

    $this->travel(23)->hours();
    postJson($session['urls']['part'], ['part' => 1])->assertGone();
    expect(app(AssetUploads::class)->cleanupExpired())->toBe(['removed' => 1, 'failed' => 0]);
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
    postJson($session['urls']['part'], ['part' => 1])->assertForbidden();
    postJson($session['urls']['complete'])->assertForbidden();
    Storage::disk('upload-destination')->assertDirectoryEmpty('/');

    app(AssetUploads::class)->allowGuestUploadsUsing($authorize);
    $part = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();
    $this->call('POST', $part['url'], cookies: $this->prepareCookiesForRequest(), server: ['CONTENT_TYPE' => 'application/octet-stream'], content: 'abc')->assertNoContent();
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
    $part = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();
    $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: $bytes)->assertNoContent();
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
    $part = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();
    $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: 'abc')->assertNoContent();
    $completed = postJson($session['urls']['complete'])->assertOk()->json();

    expect(Asset::findOne($completed['assetId'])->getPath())->toBe("{$result->element->uid}/example.txt");
});

it('keeps the staged bytes when required processing fails and retries completion', function () {
    $session = postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'example.txt', 'size' => 3, 'folderId' => $this->folder->id,
    ])->assertCreated()->json();
    $part = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();
    $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: 'abc')->assertNoContent();
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
            $part = postJson($session['urls']['part'], ['part' => $index + 1])->assertOk()->json();
            $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: $chunk)->assertNoContent();
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
    $part = postJson($session['urls']['part'], ['part' => 1])->assertOk()->json();
    $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: 'abc')->assertNoContent();

    postJson($session['urls']['complete'])->assertBadRequest()
        ->assertJsonPath('message', 'attachment.txt isn’t selectable for this field.');
    expect(Asset::find()->count())->toBe(0);
});
