<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Events\VolumeSaving;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
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
    $volume = createTestVolume();

    get(action([VolumesController::class, 'index']))->assertRedirect();
    get(action([VolumesController::class, 'create']))->assertRedirect();
    postJson(action([VolumesController::class, 'renderForm']))->assertUnauthorized();
    postJson(action([VolumesController::class, 'store']))->assertUnauthorized();
    deleteJson(action([VolumesController::class, 'destroy'], ['volumeId' => $volume->id]))->assertUnauthorized();
    postJson(action([VolumesController::class, 'reorder']))->assertUnauthorized();
});

it('requires admin changes', function () {
    $volume = createTestVolume();

    Cms::config()->allowAdminChanges = false;

    get(action([VolumesController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));

    get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', fn (Collection $nodes): bool => $nodes
                ->filter(fn (array $node): bool => isset($node['control']))
                ->every(fn (array $node): bool => $node['control']['mode'] === 'readOnly'))
            ->where('contentNotice', fn (string $notice): bool => str_contains(
                strip_tags($notice),
                t("Changes to these settings aren\u{2019}t permitted in this environment."),
            )));

    get(action([VolumesController::class, 'create']))->assertForbidden();
    postJson(action([VolumesController::class, 'renderForm']))->assertForbidden();
    postJson(action([VolumesController::class, 'store']), [
        'name' => 'Test',
        'handle' => 'test',
        'fsHandle' => 'disk:test-disk',
    ])->assertForbidden();
    deleteJson(action([VolumesController::class, 'destroy'], ['volumeId' => $volume->id]))->assertForbidden();
});

describe('index', function () {
    test('index lists all volumes', function () {
        $firstVolume = createTestVolume(['name' => 'Volume A', 'handle' => 'volumeA', 'subpath' => 'a']);
        $secondVolume = createTestVolume(['name' => 'Volume B', 'handle' => 'volumeB', 'subpath' => 'b']);

        get(action([VolumesController::class, 'index']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('volumes', fn (Collection $volumes): bool => $volumes->pluck('id')->contains($firstVolume->id)
                    && $volumes->pluck('id')->contains($secondVolume->id)));
    });
});

describe('create / edit', function () {
    test('create renders a functional form', function () {
        get(action([VolumesController::class, 'create']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', t('Create a new asset volume'))
                ->where('form.values.volumeId', null)
                ->where('form.values.name', '')
                ->where('submit.url', action([VolumesController::class, 'store']))
                ->where('form.nodes', fn (Collection $nodes): bool => $nodes->contains(
                    fn (array $node): bool => ($node['control']['path'] ?? null) === ['fsHandle']
                        && ($node['control']['props']['createUrl'] ?? null) === action([FilesystemsController::class, 'create']),
                )));
    });

    test('edit loads existing volume', function () {
        $volume = createTestVolume();

        get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', $volume->name)
                ->where('form.values.volumeId', $volume->id)
                ->where('form.values.name', $volume->name)
                ->where('form.values.handle', $volume->handle));
    });

    test('filesystem options disable targets used by other root volumes', function () {
        $volume = createTestVolume();
        $hasDiskOption = fn (bool $disabled): Closure => fn (Collection $nodes): bool => $nodes->contains(
            fn (array $node): bool => ($node['control']['path'] ?? null) === ['fsHandle']
                && collect($node['control']['props']['options'] ?? [])
                    ->flatMap(fn (array $item): array => $item['options'] ?? [$item])
                    ->contains(fn (array $option): bool => $option['value'] === 'disk:test-disk' && $option['disabled'] === $disabled),
        );

        get(action([VolumesController::class, 'create']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('form.nodes', $hasDiskOption(true)));

        get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('form.nodes', $hasDiskOption(false)));
    });

    test('submits slideout saves to the volume endpoint', function () {
        get(action([VolumesController::class, 'create']), [
            'Accept' => 'application/json',
            'X-Craft-Container-Id' => 'volume-slideout',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertOk()
            ->assertJsonPath('formAttributes.action', Url::cpUrl('settings/assets/volumes'));
    });

    test('preserves entered values when the form refreshes', function () {
        postJson(action([VolumesController::class, 'renderForm']), [
            'values' => [
                'volumeId' => null,
                'name' => 'New Volume',
                'handle' => 'newVolume',
                'fsHandle' => 'disk:test-disk',
                'subpath' => '',
                'transformFsHandle' => '',
                'transformSubpath' => '',
                'titleTranslationMethod' => 'site',
                'titleTranslationKeyFormat' => '',
                'altTranslationMethod' => 'none',
                'altTranslationKeyFormat' => '',
                'fieldLayout' => [],
            ],
            'scope' => [],
        ])
            ->assertOk()
            ->assertJsonPath('form.values.handle', 'newVolume')
            ->assertJsonPath('form.values.fsHandle', 'disk:test-disk');
    });

    test('edit returns 404 for non-existent volume', function () {
        get(action([VolumesController::class, 'edit'], ['volumeId' => 999]))
            ->assertNotFound();
    });
});

describe('store', function () {
    test('store creates volume with valid data', function () {
        postJson(action([VolumesController::class, 'store']), [
            'name' => 'New Volume',
            'handle' => 'newVolume',
            'fsHandle' => 'disk:test-disk',
        ])->assertOk();

        app()->forgetInstance(Volumes::class);
        $volume = app(Volumes::class)->getVolumeByHandle('newVolume');
        expect($volume)->not()->toBeNull();
        expect($volume->name)->toBe('New Volume');
    });

    test('store updates existing volume', function () {
        $volume = createTestVolume();

        postJson(action([VolumesController::class, 'store']), [
            'volumeId' => $volume->id,
            'name' => 'Updated Volume',
            'handle' => 'testVolume',
            'fsHandle' => 'disk:test-disk',
        ])->assertOk();

        app()->forgetInstance(Volumes::class);
        $updated = app(Volumes::class)->getVolumeByHandle('testVolume');
        expect($updated->name)->toBe('Updated Volume');
    });

    test('store returns validation errors for invalid data', function () {
        postJson(action([VolumesController::class, 'store']), [
            'name' => '',
            'handle' => '',
            'fsHandle' => '',
        ])->assertUnprocessable();
    });

    test('store validates changes made by saving event listeners', function () {
        Event::listen(VolumeSaving::class, function (VolumeSaving $event) {
            $event->volume->handle = '';
        });

        postJson(action([VolumesController::class, 'store']), [
            'name' => 'New Volume',
            'handle' => 'newVolume',
            'fsHandle' => 'disk:test-disk',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('handle');
    });
});

describe('delete', function () {
    test('delete removes volume', function () {
        $volume = createTestVolume();

        deleteJson(action([VolumesController::class, 'destroy'], ['volumeId' => $volume->id]))->assertOk();

        app()->forgetInstance(Volumes::class);
        get(action([VolumesController::class, 'index']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('volumes', fn (Collection $volumes): bool => $volumes->pluck('id')->doesntContain($volume->id)));
    });
});

describe('reorder', function () {
    test('reorder changes volume order', function () {
        $volume1 = createTestVolume(['name' => 'Volume A', 'handle' => 'volumeA', 'subpath' => 'a']);
        $volume2 = createTestVolume(['name' => 'Volume B', 'handle' => 'volumeB', 'subpath' => 'b']);

        ProjectConfig::rebuild();

        postJson(action([VolumesController::class, 'reorder']), [
            'ids' => [$volume2->id, $volume1->id],
        ])->assertOk();

        app()->forgetInstance(Volumes::class);
        get(action([VolumesController::class, 'index']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('volumes', fn (Collection $volumes): bool => $volumes->pluck('id')->all() === [$volume2->id, $volume1->id]));
    });
});
