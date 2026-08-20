<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    app(AssetTransforms::class)->extend('filesystemTest', fn () => new FilesystemTestAssetTransformDriver('secret-value'));
});

function filesystemFormControls(array $nodes): array
{
    $controls = [];

    foreach ($nodes as $node) {
        if (isset($node['control'])) {
            $controls[] = $node['control'];

            foreach ($node['control']['forms'] ?? [] as $form) {
                array_push($controls, ...filesystemFormControls($form['nodes']));
            }
        }

        array_push($controls, ...filesystemFormControls($node['children'] ?? []));
    }

    return $controls;
}

function craftAssetTransformConfig(): array
{
    return [
        'driver' => 'craft',
        'settings' => [
            'filesystem' => null,
            'subpath' => null,
            'generateBeforePageLoad' => false,
        ],
    ];
}

test('requires authentication for index', function () {
    Auth::logout();

    get(action([FilesystemsController::class, 'index']))
        ->assertRedirect();
});

test('requires authentication for edit', function () {
    Auth::logout();

    get(action([FilesystemsController::class, 'edit'], ['handle']))
        ->assertRedirect();
});

test('requires authentication for save', function () {
    Auth::logout();

    postJson(action([FilesystemsController::class, 'store']))
        ->assertUnauthorized();
});

test('requires authentication for form refresh', function () {
    Auth::logout();

    postJson(action([FilesystemsController::class, 'renderForm']))
        ->assertUnauthorized();
});

test('requires authentication for delete', function () {
    Auth::logout();

    deleteJson(action([FilesystemsController::class, 'destroy'], ['handle']))
        ->assertUnauthorized();
});

test('index lists all filesystems', function () {
    $fs = Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'Indexed Filesystem',
        'handle' => 'indexedFilesystem',
        'settings' => [
            'path' => sys_get_temp_dir().'/indexed-filesystem',
        ],
    ]);
    Filesystems::saveFilesystem($fs);

    get(action([FilesystemsController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filesystems.data', fn ($filesystems): bool => collect($filesystems)
                ->contains('handle', 'indexedFilesystem')));
});

test('index shows read-only flag when allowAdminChanges is false', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([FilesystemsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
});

test('edit shows 403 when creating in read-only mode', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([FilesystemsController::class, 'create']))
        ->assertForbidden();
});

test('create renders a functional filesystem form', function () {
    get(action([FilesystemsController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('form.values.oldHandle', null)
            ->where('form.values.type', Local::class)
            ->where('form.values.assetTransform.driver', 'craft')
            ->where('form.values.assetTransform.settings.filesystem', null)
            ->where('form.values.assetTransform.settings.subpath', '')
            ->where('form.values.assetTransform.settings.generateBeforePageLoad', false)
            ->where('submit.url', action([FilesystemsController::class, 'store']))
            ->where('refreshUrl', action([FilesystemsController::class, 'renderForm']))
            ->where('form.nodes', function ($nodes): bool {
                $paths = collect(filesystemFormControls(collect($nodes)->all()))->pluck('path');

                return $paths->contains(['name'])
                    && $paths->contains(['handle'])
                    && $paths->contains(['type'])
                    && $paths->contains(['settings', 'path']);
            }));
});

test('slideout form targets the filesystem CP route', function () {
    get(action([FilesystemsController::class, 'create']), [
        'Accept' => 'application/json',
        'X-Craft-Container-Id' => 'filesystem-slideout',
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('formAttributes.action', Url::cpUrl('settings/filesystems'));
});

test('edit returns 404 for non-existent filesystem handle', function () {
    get(action([FilesystemsController::class, 'edit'], ['handle' => 'non-existent-handle']))
        ->assertNotFound();
});

test('edit loads existing filesystem by handle', function () {
    $fs = Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'Test Filesystem',
        'handle' => 'testFilesystem',
        'settings' => [
            'path' => sys_get_temp_dir().'/test-filesystem',
            'hasUrls' => false,
            'url' => '@web/uploads',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    get(action([FilesystemsController::class, 'edit'], ['handle' => 'testFilesystem']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('form.values.name', 'Test Filesystem')
            ->where('form.values.handle', 'testFilesystem')
            ->where('form.values.oldHandle', 'testFilesystem')
            ->where('form.values.settings.path', File::normalizePath(sys_get_temp_dir().'/test-filesystem', '/'))
            ->where('form.values.settings.url', '@web/uploads')
            ->where('form.nodes', fn ($nodes): bool => collect(filesystemFormControls(collect($nodes)->all()))
                ->pluck('path')
                ->doesntContain(['settings', 'url'])));
});

test('edit renders existing filesystems as read-only when admin changes are disabled', function () {
    Filesystems::saveFilesystem(Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'Read-only Filesystem',
        'handle' => 'readOnlyFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/read-only-filesystem'],
    ]));
    Cms::config()->allowAdminChanges = false;

    get(action([FilesystemsController::class, 'edit'], ['handle' => 'readOnlyFilesystem']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('refreshUrl', null)
            ->where('form.nodes', fn ($nodes): bool => collect(filesystemFormControls(collect($nodes)->all()))
                ->every(fn (array $control): bool => $control['mode'] === 'readOnly')));
});

test('save creates filesystem with valid data', function () {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'New Test Filesystem',
        'handle' => 'newTestFilesystem',
        'settings' => [
            'path' => sys_get_temp_dir().'/test-uploads',
        ],
        'assetTransform' => craftAssetTransformConfig(),
    ])->assertOk();

    $fs = Filesystems::getFilesystemByHandle('newTestFilesystem');
    expect($fs)->not()->toBeNull()
        ->and($fs->name)->toBe('New Test Filesystem')
        ->and($fs->getSettings())->toMatchArray([
            'path' => File::normalizePath(sys_get_temp_dir().'/test-uploads', '/'),
        ])
        ->and($fs->getAssetTransform())->toEqual(craftAssetTransformConfig());
});

test('save persists one complete Asset Transform override unchanged', function () {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Transformed Filesystem',
        'handle' => 'transformedFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/transformed-filesystem'],
        'assetTransform' => [
            'driver' => 'filesystemTest',
            'settings' => [
                'endpoint' => 'https://uploads.example.test',
                'enabled' => true,
            ],
        ],
    ])->assertOk();

    $assetTransform = Filesystems::getFilesystemByHandle('transformedFilesystem')?->getAssetTransform();

    expect($assetTransform)->toMatchArray([
        'driver' => 'filesystemTest',
        'settings' => [
            'endpoint' => 'https://uploads.example.test',
            'enabled' => true,
        ],
    ])->and(json_encode($assetTransform))->not->toContain('secret-value');
});

test('save rejects incomplete Asset Transform settings', function () {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Incomplete Transform Filesystem',
        'handle' => 'incompleteTransformFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/incomplete-transform-filesystem'],
        'assetTransform' => [
            'driver' => 'filesystemTest',
            'settings' => [
                'enabled' => true,
                'stale' => 'discard me',
            ],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('assetTransform.settings');
});

test('save accepts a registered legacy class driver handle', function () {
    $driver = FilesystemTestAssetTransformDriver::class;
    app(AssetTransforms::class)->extend($driver, fn () => new FilesystemTestAssetTransformDriver('secret-value'));

    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Legacy Driver Filesystem',
        'handle' => 'legacyDriverFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/legacy-driver-filesystem'],
        'assetTransform' => [
            'driver' => $driver,
            'settings' => [
                'endpoint' => 'https://example.test',
                'enabled' => false,
            ],
        ],
    ])->assertOk();

    expect(Filesystems::getFilesystemByHandle('legacyDriverFilesystem')?->getAssetTransform()['driver'])->toBe($driver);
});

test('save rejects an unavailable Asset Transform driver', function () {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Broken Filesystem',
        'handle' => 'brokenFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/broken-filesystem'],
        'assetTransform' => ['driver' => 'missing', 'settings' => []],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('assetTransform.driver');
});

test('save validates the Asset Transform request shape', function (mixed $assetTransform, string $error) {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Invalid Transform Filesystem',
        'handle' => 'invalidTransformFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/invalid-transform-filesystem'],
        'assetTransform' => $assetTransform,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'configuration' => ['invalid', 'assetTransform'],
    'missing driver' => [[], 'assetTransform.driver'],
    'settings' => [[
        'driver' => 'filesystemTest',
        'settings' => 'invalid',
    ], 'assetTransform.settings'],
]);

test('refreshes filesystem settings without saving', function () {
    postJson(action([FilesystemsController::class, 'renderForm']), [
        'values' => [
            'type' => Local::class,
            'name' => 'Uploads',
            'handle' => 'uploads',
            'oldHandle' => null,
            'settings' => [
                'hasUrls' => true,
                'url' => '@web/uploads',
                'path' => sys_get_temp_dir().'/uploads',
            ],
            'assetTransform' => craftAssetTransformConfig(),
        ],
        'scope' => [],
    ])->assertOk()
        ->assertJsonPath('form.scope', [])
        ->assertJsonPath('form.values.name', 'Uploads')
        ->assertJsonPath('form.values.settings.url', '@web/uploads');

    expect(Filesystems::getFilesystemByHandle('uploads'))->toBeNull();
});

test('refresh rebuilds Asset Transform settings from the selected driver', function () {
    postJson(action([FilesystemsController::class, 'renderForm']), [
        'values' => [
            'type' => Local::class,
            'name' => 'Uploads',
            'handle' => 'uploads',
            'oldHandle' => null,
            'settings' => ['path' => sys_get_temp_dir().'/uploads'],
            'assetTransform' => [
                'driver' => 'filesystemTest',
                'settings' => ['stale' => 'discard me'],
            ],
        ],
        'scope' => [],
    ])->assertOk()
        ->assertJsonPath('form.values.assetTransform.settings.endpoint', 'https://example.test')
        ->assertJsonPath('form.values.assetTransform.settings.enabled', false)
        ->assertJsonMissingPath('form.values.assetTransform.settings.stale');
});

test('refresh omits controls that do not apply to the current settings', function () {
    $values = [
        'type' => Local::class,
        'name' => 'Uploads',
        'handle' => 'uploads',
        'oldHandle' => null,
        'settings' => [
            'hasUrls' => false,
            'url' => '@web/uploads',
            'path' => sys_get_temp_dir().'/uploads',
        ],
        'assetTransform' => craftAssetTransformConfig(),
    ];

    $withoutBaseUrl = postJson(action([FilesystemsController::class, 'renderForm']), [
        'values' => $values,
        'scope' => [],
    ])->json('form.nodes');
    $withBaseUrl = postJson(action([FilesystemsController::class, 'renderForm']), [
        'values' => [...$values, 'settings' => [...$values['settings'], 'hasUrls' => true]],
        'scope' => [],
    ])->json('form.nodes');

    expect(collect(filesystemFormControls($withoutBaseUrl))->pluck('path'))->not->toContain(['settings', 'url'])
        ->and(collect(filesystemFormControls($withBaseUrl))->pluck('path'))->toContain(['settings', 'url']);
});

test('refresh rejects unregistered filesystem types', function () {
    postJson(action([FilesystemsController::class, 'renderForm']), [
        'values' => ['type' => stdClass::class],
        'scope' => [],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('values.type');
});

test('save updates existing filesystem with oldHandle', function () {
    $fs = Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'Original Name',
        'handle' => 'originalHandle',
        'settings' => [
            'path' => sys_get_temp_dir().'/original',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Updated Name',
        'handle' => 'updatedHandle',
        'oldHandle' => 'originalHandle',
        'assetTransform' => craftAssetTransformConfig(),
    ])->assertOk();

    $newFs = Filesystems::getFilesystemByHandle('updatedHandle');
    expect(Filesystems::getFilesystemByHandle('originalHandle'))->toBeNull()
        ->and($newFs)->not()->toBeNull()
        ->and($newFs->name)->toBe('Updated Name')
        ->and($newFs->getSettings())->toMatchArray([
            'path' => File::normalizePath(sys_get_temp_dir().'/original', '/'),
        ]);
});

test('save does not carry settings across filesystem types', function () {
    Filesystems::saveFilesystem(Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'Original Filesystem',
        'handle' => 'originalFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/original-filesystem'],
    ]));

    postJson(action([FilesystemsController::class, 'store']), [
        'type' => TransientSettingsFilesystem::class,
        'name' => 'Changed Filesystem',
        'handle' => 'changedFilesystem',
        'oldHandle' => 'originalFilesystem',
        'settings' => ['path' => sys_get_temp_dir().'/stale-local-path'],
        'assetTransform' => craftAssetTransformConfig(),
    ])->assertOk();

    $filesystem = Filesystems::getFilesystemByHandle('changedFilesystem');

    expect(Filesystems::getFilesystemByHandle('originalFilesystem'))->toBeNull()
        ->and($filesystem)->toBeInstanceOf(TransientSettingsFilesystem::class)
        ->and($filesystem->getSettings())->not->toHaveKey('path');
});

test('save returns failure on invalid data', function () {
    $response = postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => '',
        'handle' => '',
        'settings' => [],
        'assetTransform' => craftAssetTransformConfig(),
    ]);

    $response
        ->assertStatus(400)
        ->assertJsonStructure(['errors' => ['name', 'handle', 'settings.path']]);
});

test('delete removes filesystem successfully', function () {
    $fs = Filesystems::createFilesystem([
        'type' => Local::class,
        'name' => 'To Delete',
        'handle' => 'toDelete',
        'settings' => [
            'path' => sys_get_temp_dir().'/to-delete',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    deleteJson(action([FilesystemsController::class, 'destroy'], [$fs->handle]))
        ->assertOk();

    expect(Filesystems::getFilesystemByHandle('toDelete'))->toBeNull();
});

test('delete handles non-existent filesystem gracefully', function () {
    deleteJson(action([FilesystemsController::class, 'destroy'], ['non-existent-filesystem']))
        ->assertOk();
});

test('respects read-only mode for save operation', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([FilesystemsController::class, 'store']))
        ->assertForbidden();
});

test('respects read-only mode for delete operation', function () {
    Cms::config()->allowAdminChanges = false;

    deleteJson(action([FilesystemsController::class, 'destroy'], ['test']))
        ->assertForbidden();
});

class TransientSettingsFilesystem extends Filesystem
{
    #[Override]
    public function getDiskConfig(): array
    {
        return [
            'driver' => 'local',
            'root' => sys_get_temp_dir(),
        ];
    }
}

class FilesystemTestAssetTransformDriver implements AssetTransformDriver
{
    public function __construct(
        #[SensitiveParameter]
        private readonly string $credential,
    ) {}

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition($this->credential !== '' ? 'Filesystem Test' : 'Unavailable', filesystemSettings: [
            Field::make('Endpoint', Text::make('endpoint')->value('https://example.test')),
            Field::make('Enabled', Lightswitch::make('enabled')->value(false)),
        ]);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        return new AssetTransformResult('/unused', 'image/jpeg');
    }
}
