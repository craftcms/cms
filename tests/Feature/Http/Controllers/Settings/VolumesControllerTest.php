<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Events\VolumeSaving;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\VolumesController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

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
        'fsHandle' => 'test-disk',
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
    postJson(action([VolumesController::class, 'renderUi']))->assertUnauthorized();
    postJson(action([VolumesController::class, 'store']))->assertUnauthorized();
    deleteJson(action([VolumesController::class, 'destroy'], ['volumeId' => $volume->id]))->assertUnauthorized();
    postJson(action([VolumesController::class, 'reorder']))->assertUnauthorized();
});

it('requires admin changes', function () {
    $volume = createTestVolume();

    Cms::config()->allowAdminChanges = false;

    get(action([VolumesController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('readOnly', true)
            ->where('ui.nodes.0.props.createUrl', null)
            ->where('ui.nodes.0.props.reorderUrl', null)
            ->where('ui.nodes.0.props.deletable', false)
            ->where('ui.nodes.0.props.rows.0.name.url', action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->missing('ui.nodes.0.props.rows.0._deleteUrl'));

    get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes', fn (Collection $nodes): bool => $nodes
                ->filter(fn (array $node): bool => isset($node['control']))
                ->every(fn (array $node): bool => $node['control']['mode'] === 'readOnly'))
            ->where('contentNotice', fn (string $notice): bool => str_contains(
                strip_tags($notice),
                t("Changes to these settings aren\u{2019}t permitted in this environment."),
            )));

    get(action([VolumesController::class, 'create']))->assertForbidden();
    postJson(action([VolumesController::class, 'renderUi']))->assertForbidden();
    postJson(action([VolumesController::class, 'store']), [
        'name' => 'Test',
        'handle' => 'test',
        'fsHandle' => 'test-disk',
    ])->assertForbidden();
    deleteJson(action([VolumesController::class, 'destroy'], ['volumeId' => $volume->id]))->assertForbidden();
    postJson(action([VolumesController::class, 'reorder']), ['ids' => [$volume->id]])->assertForbidden();
});

describe('index', function () {
    test('index preserves manual order with edit links, copyable handles, and named deletion', function () {
        $firstVolume = createTestVolume(['name' => 'Volume B', 'handle' => 'volumeB', 'subpath' => 'b']);
        $secondVolume = createTestVolume(['name' => 'Volume A', 'handle' => 'volumeA', 'subpath' => 'a']);

        get(action([VolumesController::class, 'index']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ui')
                ->where('ui.nodes.0.props.reorderUrl', action([VolumesController::class, 'reorder']))
                ->where('ui.nodes.0.props.deletable', true)
                ->where('ui.nodes.0.props.rows', function (Collection $rows) use ($firstVolume, $secondVolume): bool {
                    expect($rows->pluck('id')->all())->toBe([$firstVolume->id, $secondVolume->id]);

                    $row = $rows[0];
                    $handle = new Crawler($row['handle']['html'])->filter('craft-copy-attribute');

                    expect($row['name']['label'])->toBe('Volume B')
                        ->and($row['name']['url'])->toBe(action([VolumesController::class, 'edit'], ['volumeId' => $firstVolume->id]))
                        ->and($row['_deleteUrl'])->toBe(action([VolumesController::class, 'destroy'], ['volumeId' => $firstVolume->id]))
                        ->and($row['_deleteConfirmMessage'])->toBe('Are you sure you want to delete "Volume B"?')
                        ->and($handle->attr('value'))->toBe('volumeB')
                        ->and($handle->text())->toBe('volumeB');

                    return true;
                }));
    });

    test('empty index offers volume creation', function () {
        get(action([VolumesController::class, 'index']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Ui')
                ->where('ui.nodes.0.props.rows', [])
                ->where('ui.nodes.0.props.emptyMessage', t('No volumes exist yet.'))
                ->where('ui.nodes.0.props.createLabel', t('New volume'))
                ->where('ui.nodes.0.props.createUrl', action([VolumesController::class, 'create'])));
    });
});

describe('create / edit', function () {
    test('create renders a functional UI', function () {
        get(action([VolumesController::class, 'create']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', t('Create a new asset volume'))
                ->where('ui.values.volumeId', null)
                ->where('ui.values.name', '')
                ->where('ui.values.assetTransformer', '')
                ->where('submit.url', action([VolumesController::class, 'store']))
                ->where('ui.nodes', function (Collection $nodes): bool {
                    $paths = $nodes->pluck('control.path');

                    return $nodes->contains(
                        fn (array $node): bool => ($node['control']['path'] ?? null) === ['fsHandle']
                            && collect($node['control']['props']['options'] ?? [])->contains('value', 'test-disk'),
                    )
                        && $paths->contains(['hasUrls'])
                        && $nodes->contains(
                            fn (array $node): bool => ($node['control']['path'] ?? null) === ['subpath']
                                && ! empty($node['control']['props']['textExpanderTriggers']),
                        )
                        && $paths->doesntContain(['transformFsHandle'])
                        && $paths->doesntContain(['transformSubpath'])
                        && $paths->contains(['assetTransformer']);
                }));
    });

    test('edit loads existing volume', function () {
        $volume = createTestVolume();

        get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', $volume->name)
                ->where('ui.values.volumeId', $volume->id)
                ->where('ui.values.name', $volume->name)
                ->where('ui.values.handle', $volume->handle));
    });

    test('filesystem options disable targets used by other root volumes', function () {
        $volume = createTestVolume();
        $hasDiskOption = fn (bool $disabled): Closure => fn (Collection $nodes): bool => $nodes->contains(
            fn (array $node): bool => ($node['control']['path'] ?? null) === ['fsHandle']
                && collect($node['control']['props']['options'] ?? [])
                    ->flatMap(fn (array $item): array => $item['options'] ?? [$item])
                    ->contains(fn (array $option): bool => $option['value'] === 'test-disk' && $option['disabled'] === $disabled),
        );

        get(action([VolumesController::class, 'create']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ui.nodes', $hasDiskOption(true)));

        get(action([VolumesController::class, 'edit'], ['volumeId' => $volume->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ui.nodes', $hasDiskOption(false)));
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

    test('preserves entered values when the UI refreshes', function () {
        postJson(action([VolumesController::class, 'renderUi']), [
            'values' => [
                'volumeId' => null,
                'name' => 'New Volume',
                'handle' => 'newVolume',
                'fsHandle' => 'test-disk',
                'hasUrls' => true,
                'subpath' => '',
                'assetTransformer' => 'craft',
                'titleTranslationMethod' => 'site',
                'titleTranslationKeyFormat' => '',
                'altTranslationMethod' => 'none',
                'altTranslationKeyFormat' => '',
                'fieldLayout' => [],
            ],
            'scope' => [],
        ])
            ->assertOk()
            ->assertJsonPath('ui.values.handle', 'newVolume')
            ->assertJsonPath('ui.values.fsHandle', 'test-disk')
            ->assertJsonPath('ui.values.hasUrls', true)
            ->assertJsonPath('ui.values.assetTransformer', 'craft');
    });

    test('edit returns 404 for non-existent volume', function () {
        get(action([VolumesController::class, 'edit'], ['volumeId' => 999]))
            ->assertNotFound();
    });
});

describe('store', function () {
    test('store creates volume with valid data', function () {
        $response = postJson(
            action([VolumesController::class, 'store']),
            [
                'name' => 'New Volume',
                'handle' => 'newVolume',
                'fsHandle' => 'test-disk',
                'hasUrls' => true,
                'assetTransformer' => 'craft',
            ],
            ['Accept' => 'text/html', 'X-Inertia' => 'true'],
        );

        app()->forgetInstance(Volumes::class);
        $volume = app(Volumes::class)->getVolumeByHandle('newVolume');
        expect($volume)->not()->toBeNull();
        expect($volume->name)->toBe('New Volume')
            ->and($volume->hasUrls)->toBeTrue()
            ->and($volume->getAssetTransformerHandle(false))->toBe('craft');
        $response->assertRedirect(Url::cpUrl("settings/assets/volumes/{$volume->id}"));
    });

    test('store updates existing volume', function () {
        $volume = createTestVolume();

        postJson(action([VolumesController::class, 'store']), [
            'volumeId' => $volume->id,
            'name' => 'Updated Volume',
            'handle' => 'testVolume',
            'fsHandle' => 'test-disk',
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

    test('store validates the Asset Transformer reference shape', function () {
        postJson(action([VolumesController::class, 'store']), [
            'name' => 'Invalid Transform Volume',
            'handle' => 'invalidTransformVolume',
            'fsHandle' => 'test-disk',
            'assetTransformer' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('assetTransformer');
    });

    test('store validates changes made by saving event listeners', function () {
        Event::listen(VolumeSaving::class, function (VolumeSaving $event) {
            $event->volume->handle = '';
        });

        postJson(action([VolumesController::class, 'store']), [
            'name' => 'New Volume',
            'handle' => 'newVolume',
            'fsHandle' => 'test-disk',
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
                ->where('ui.nodes.0.props.rows', fn (Collection $rows): bool => $rows->pluck('id')->doesntContain($volume->id)));
    });
});

describe('reorder', function () {
    test('rejects malformed reorder IDs without changing volume order', function (Closure $ids) {
        $first = createTestVolume(['name' => 'Volume A', 'handle' => 'volumeA', 'subpath' => 'a']);
        $second = createTestVolume(['name' => 'Volume B', 'handle' => 'volumeB', 'subpath' => 'b']);
        ProjectConfig::rebuild();

        postJson(action([VolumesController::class, 'reorder']), ['ids' => $ids($first->id, $second->id)])
            ->assertUnprocessable();

        app()->forgetInstance(Volumes::class);
        expect(app(Volumes::class)->getAllVolumes()->pluck('id')->all())->toBe([$first->id, $second->id]);
    })->with([
        'JSON string' => [fn (int $first, int $second): string => json_encode([$second, $first])],
        'duplicate IDs' => [fn (int $first, int $second): array => [$second, $first, $second]],
        'non-integer ID' => [fn (int $first, int $second): array => [$second, 'invalid', $first]],
    ]);

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
                ->where('ui.nodes.0.props.rows', fn (Collection $rows): bool => $rows->pluck('id')->all() === [$volume2->id, $volume1->id]));
    });
});
