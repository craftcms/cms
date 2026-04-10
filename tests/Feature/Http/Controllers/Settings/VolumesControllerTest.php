<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volumes-test/test-disk'),
    ]);

    $this->volumes = app(Volumes::class);
});

function createTestVolume(array $overrides = []): VolumeData
{
    $volumes = app(Volumes::class);

    $volume = new VolumeData(array_merge([
        'name' => 'Test Volume',
        'handle' => 'testVolume',
        'fsHandle' => 'disk:test-disk',
    ], $overrides));

    $volumes->saveVolume($volume);

    app()->forgetInstance(Volumes::class);

    return app(Volumes::class)->getVolumeByHandle($volume->handle);
}

it('requires authentication', function () {
    Auth::logout();

    get(action([VolumesController::class, 'index']))->assertRedirect();
    get(action([VolumesController::class, 'create']))->assertRedirect();
    postJson(action([VolumesController::class, 'save']))->assertUnauthorized();
    postJson(action([VolumesController::class, 'destroy']))->assertUnauthorized();
    postJson(action([VolumesController::class, 'reorder']))->assertUnauthorized();
});

it('requires admin changes', function () {
    $volume = createTestVolume();

    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([VolumesController::class, 'index']))
        ->assertOk()
        ->assertSee(t("Changes to these settings aren\u{2019}t permitted in this environment."));
    get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
        ->assertOk()
        ->assertSee(t("Changes to these settings aren\u{2019}t permitted in this environment."));

    // Not allowed
    get(action([VolumesController::class, 'create']))->assertForbidden();
    postJson(action([VolumesController::class, 'save']), [
        'name' => 'Test',
        'handle' => 'test',
        'fsHandle' => 'disk:test-disk',
    ])->assertForbidden();
    postJson(action([VolumesController::class, 'destroy']), [
        'id' => 1,
    ])->assertForbidden();
});

describe('index', function () {
    test('index lists all volumes', function () {
        get(action([VolumesController::class, 'index']))
            ->assertOk();
    });
});

describe('create / edit', function () {
    test('create delegates to edit method', function () {
        get(action([VolumesController::class, 'create']))
            ->assertOk()
            ->assertSee(t('Create a new asset volume'));
    });

    test('edit loads existing volume', function () {
        $volume = createTestVolume();

        get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->assertOk()
            ->assertSee($volume->name);
    });

    test('edit returns 404 for non-existent volume', function () {
        get(action([VolumesController::class, 'edit'], ['volumeId' => 999]))
            ->assertNotFound();
    });
});

describe('save', function () {
    test('save creates volume with valid data', function () {
        expect(Volume::count())->toBe(0);

        postJson(action([VolumesController::class, 'save']), [
            'name' => 'New Volume',
            'handle' => 'newVolume',
            'fsHandle' => 'disk:test-disk',
        ])->assertOk();

        expect(Volume::count())->toBe(1);

        app()->forgetInstance(Volumes::class);
        $volume = app(Volumes::class)->getVolumeByHandle('newVolume');
        expect($volume)->not()->toBeNull();
        expect($volume->name)->toBe('New Volume');
    });

    test('save updates existing volume', function () {
        $volume = createTestVolume();

        postJson(action([VolumesController::class, 'save']), [
            'volumeId' => $volume->id,
            'name' => 'Updated Volume',
            'handle' => 'testVolume',
            'fsHandle' => 'disk:test-disk',
        ])->assertOk();

        app()->forgetInstance(Volumes::class);
        $updated = app(Volumes::class)->getVolumeByHandle('testVolume');
        expect($updated->name)->toBe('Updated Volume');
    });

    test('save returns failure on invalid data', function () {
        postJson(action([VolumesController::class, 'save']), [
            'name' => '',
            'handle' => '',
            'fsHandle' => '',
        ])->assertStatus(400);
    });
});

describe('delete', function () {
    test('delete removes volume', function () {
        $volume = createTestVolume();

        expect(Volume::count())->toBe(1);

        postJson(action([VolumesController::class, 'destroy']), [
            'id' => $volume->id,
        ])->assertOk();

        expect(Volume::withTrashed()->whereNotNull('dateDeleted')->count())->toBe(1);
    });

    test('delete validates required id field', function () {
        postJson(action([VolumesController::class, 'destroy']), [])
            ->assertJsonValidationErrors(['id']);
    });
});

describe('reorder', function () {
    test('reorder changes volume order', function () {
        $volume1 = createTestVolume(['name' => 'Volume A', 'handle' => 'volumeA', 'subpath' => 'a']);
        $volume2 = createTestVolume(['name' => 'Volume B', 'handle' => 'volumeB', 'subpath' => 'b']);

        ProjectConfig::rebuild();

        expect(Volume::findOrFail($volume1->id)->sortOrder)->toBe(1);
        expect(Volume::findOrFail($volume2->id)->sortOrder)->toBe(2);

        postJson(action([VolumesController::class, 'reorder']), [
            'ids' => Json::encode([$volume2->id, $volume1->id]),
        ])->assertOk();

        expect(Volume::findOrFail($volume2->id)->sortOrder)->toBe(1);
        expect(Volume::findOrFail($volume1->id)->sortOrder)->toBe(2);
    });
});
