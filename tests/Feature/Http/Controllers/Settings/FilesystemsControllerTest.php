<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Filesystem\Filesystems\Local;
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
    ])->assertOk();

    $fs = Filesystems::getFilesystemByHandle('newTestFilesystem');
    expect($fs)->not()->toBeNull()
        ->and($fs->name)->toBe('New Test Filesystem')
        ->and($fs->getSettings())->toMatchArray([
            'path' => File::normalizePath(sys_get_temp_dir().'/test-uploads', '/'),
        ]);
});

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
        ],
        'scope' => [],
    ])->assertOk()
        ->assertJsonPath('form.scope', [])
        ->assertJsonPath('form.values.name', 'Uploads')
        ->assertJsonPath('form.values.settings.url', '@web/uploads');

    expect(Filesystems::getFilesystemByHandle('uploads'))->toBeNull();
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
    ]);

    $response
        ->assertStatus(400)
        ->assertJsonStructure(['errors' => ['name', 'handle', 'settings.path']]);
});

test('save rejects missing environment variables for required settings', function () {
    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Missing Path',
        'handle' => 'missingPath',
        'settings' => [
            'path' => '$FILESYSTEM_SETTINGS_MISSING',
        ],
    ])->assertStatus(400)
        ->assertJsonStructure(['errors' => ['settings.path']]);

    postJson(action([FilesystemsController::class, 'store']), [
        'type' => Local::class,
        'name' => 'Missing URL',
        'handle' => 'missingUrl',
        'settings' => [
            'hasUrls' => true,
            'url' => '$FILESYSTEM_SETTINGS_MISSING',
            'path' => sys_get_temp_dir().'/missing-url',
        ],
    ])->assertStatus(400)
        ->assertJsonStructure(['errors' => ['settings.url']]);
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
