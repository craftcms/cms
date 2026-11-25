<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\AuthorizeUploadEvent;
use craft\services\Uploads as LegacyUploads;
use craft\web\assets\uploads\UploadsAsset;
use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Http\Controllers\Assets\UploadSessionController;
use CraftCms\Cms\Http\Middleware\PreventRequestForgery;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTestCase::class);

beforeEach(function() {
    config()->set('filesystems.disks.upload-parts', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-parts')]);
    config()->set('filesystems.disks.upload-destination', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-destination')]);
    Storage::fake('upload-parts');
    Storage::fake('upload-destination');
    Cms::config()->uploadSessionDisk = 'upload-parts';

    $this->volume = Volume::factory()->create(['disk' => 'upload-destination']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id, 'path' => '']);
    $this->start = fn(array $parameters = []) => $this->postJson(action([UploadSessionController::class, 'store']), [
        'filename' => 'proof.txt', 'size' => 3, 'folderId' => $this->folder->id, ...$parameters,
    ]);
});

afterEach(function() {
    YiiEvent::off(LegacyUploads::class, LegacyUploads::EVENT_AUTHORIZE_UPLOAD);
});

it('lets authorize upload handlers allow guests', function() {
    $events = [];
    YiiEvent::on(LegacyUploads::class, LegacyUploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) use (&$events) {
        $events[] = clone $event;
        $event->isValid = $event->params['folderId'] === $this->folder->id && $event->params['context'] === ['form' => 'apply'];
    });

    ($this->start)(['context' => ['form' => 'other']])->assertForbidden();
    ($this->start)(['context' => ['form' => 'apply']])->assertCreated();

    expect(UploadSession::count())->toBe(1)
        ->and($events[1]->handler)->toBe(AssetUploads::class)
        ->and($events[1]->filename)->toBe('proof.txt')
        ->and($events[1]->size)->toBe(3)
        ->and($events[1]->params)->toBe(['operation' => 'upload', 'folderId' => $this->folder->id, 'context' => ['form' => 'apply']])
        ->and($events[1]->user)->toBeNull()
        ->and($events[1]->isValid)->toBeFalse();
});

it('passes the application guest authorizer’s decision to authorize upload handlers', function() {
    app(AssetUploads::class)->allowGuestUploadsUsing(fn(Request $request, array $file): bool => $file['size'] <= 3);
    $isValid = [];
    YiiEvent::on(LegacyUploads::class, LegacyUploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) use (&$isValid) {
        $isValid[] = $event->isValid;
    });

    ($this->start)()->assertCreated();
    ($this->start)(['size' => 4])->assertForbidden();

    expect($isValid)->toBe([true, false]);
});

it('keeps guests out without an authorize upload handler or guest authorizer', function() {
    ($this->start)()->assertUnauthorized();

    expect(UploadSession::count())->toBe(0);
});

it('lets authorize upload handlers deny users', function() {
    $user = User::findOne();
    $this->actingAs($user);
    $events = [];
    YiiEvent::on(LegacyUploads::class, LegacyUploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) use (&$events) {
        $events[] = clone $event;
        $event->isValid = $event->filename !== 'denied.txt';
    });

    ($this->start)(['filename' => 'denied.txt'])->assertForbidden();
    ($this->start)()->assertCreated();

    expect(UploadSession::count())->toBe(1)
        ->and($events[0]->user?->id)->toBe($user->id)
        ->and($events[0]->isValid)->toBeTrue();
});

it('deletes expired uploads through the legacy uploads service', function() {
    $this->actingAs(User::findOne());
    ($this->start)()->assertCreated();
    ($this->start)()->assertCreated();
    UploadSession::query()->limit(1)->update(['expiresAt' => now()->subMinute()]);

    expect(Craft::$app->getUploads()->deleteExpiredUploads())->toBe(1)
        ->and(UploadSession::count())->toBe(1);
});

it('loads the uploads entry point with its defaults', function(bool $originOnly) {
    $property = new ReflectionProperty(PreventRequestForgery::class, 'originOnly');
    $original = $property->getValue();
    $property->setValue(null, $originOnly);
    session()->start();

    try {
        (new UploadsAsset())->registerAssetFiles(Craft::$app->getView());
        app(InternalAssetRegistry::class)->flush();
        $html = app(HtmlStack::class)->headHtml() . app(HtmlStack::class)->bodyEndHtml();
    } finally {
        $property->setValue(null, $original);
    }

    expect($html)->toContain(Cp::viteTags(['resources/js/uploads.ts']))
        ->toContain('<script type="module">Object.assign(Craft.Uploads.defaults, ')
        ->toContain(str_replace('/', '\/', route('craft.actions.craft.uploads.store')));

    if ($originOnly) {
        expect($html)->not->toContain('csrfToken');
    } else {
        expect($html)->toContain('"csrfTokenName":"_token"')->toContain(sprintf('"csrfToken":"%s"', csrf_token()));
    }
})->with([
    'tokens' => false,
    'origin only' => true,
]);
