<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('requires authentication for index', function () {
    Auth::logout();

    get(action([FilesystemsController::class, 'index']))
        ->assertRedirect();
});

test('requires authentication for edit', function () {
    Auth::logout();

    get(action([FilesystemsController::class, 'edit']))
        ->assertRedirect();
});

test('requires authentication for save', function () {
    Auth::logout();

    postJson(action([FilesystemsController::class, 'save']))
        ->assertUnauthorized();
});

test('requires authentication for delete', function () {
    Auth::logout();

    deleteJson(action([FilesystemsController::class, 'destroy'], ['handle']))
        ->assertUnauthorized();
});

test('index lists all filesystems', function () {
    get(action([FilesystemsController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Filesystems'));
});

test('index shows read-only flag when allowAdminChanges is false', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([FilesystemsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
});

test('create delegates to edit method', function () {
    $response = get(action([FilesystemsController::class, 'create']));

    $response
        ->assertOk()
        ->assertSee(t('Create a new filesystem'));
});

test('edit shows 403 when creating in read-only mode', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([FilesystemsController::class, 'edit']))
        ->assertForbidden();
});

test('edit shows create form for new filesystem', function () {
    $response = get(action([FilesystemsController::class, 'edit']));

    $response
        ->assertOk()
        ->assertSee(t('Create a new filesystem'));
});

test('edit returns 200 for non-existent filesystem handle and shows create form', function () {
    // Non-existent handles are treated as new filesystem creation
    $response = get(action([FilesystemsController::class, 'edit'], ['non-existent-handle']));

    $response
        ->assertOk()
        ->assertSee(t('Create a new filesystem'));
});

test('edit loads existing filesystem by handle', function () {
    // Create a test filesystem
    $fs = Filesystems::createFilesystem([
        'type' => 'craft\fs\Local',
        'name' => 'Test Filesystem',
        'handle' => 'testFilesystem',
        'settings' => [
            'path' => '@webroot/uploads',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    $response = get(action([FilesystemsController::class, 'edit'], ['testFilesystem']));

    $response->assertOk();

    // Verify filesystem exists after save
    $savedFs = Filesystems::getFilesystemByHandle('testFilesystem');
    expect($savedFs)->not()->toBeNull();
    expect($savedFs->name)->toBe('Test Filesystem');
});

test('edit loads filesystem and shows actions when not in read-only mode', function () {
    // Create a test filesystem first
    $fs = Filesystems::createFilesystem([
        'type' => 'craft\fs\Local',
        'name' => 'Test Filesystem For Actions',
        'handle' => 'testFilesystemActions',
        'settings' => [
            'path' => '@webroot/uploads',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    $response = get(action([FilesystemsController::class, 'edit'], ['testFilesystemActions']));

    $response->assertOk();
});

test('save creates filesystem with valid data', function () {
    $response = postJson(action([FilesystemsController::class, 'save']), [
        'type' => 'craft\fs\Local',
        'name' => 'New Test Filesystem',
        'handle' => 'newTestFilesystem',
        'types' => [
            'craft-fs-Local' => [
                'path' => '@webroot/test-uploads',
            ],
        ],
    ]);

    $response->assertOk();

    // Verify filesystem was created
    $fs = Filesystems::getFilesystemByHandle('newTestFilesystem');
    expect($fs)->not()->toBeNull();
    expect($fs->name)->toBe('New Test Filesystem');
});

test('save updates existing filesystem with oldHandle', function () {
    // Create a filesystem first
    $fs = Filesystems::createFilesystem([
        'type' => 'craft\fs\Local',
        'name' => 'Original Name',
        'handle' => 'originalHandle',
        'settings' => [
            'path' => '@webroot/original',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    // Update it
    $response = postJson(action([FilesystemsController::class, 'save']), [
        'type' => 'craft\fs\Local',
        'name' => 'Updated Name',
        'handle' => 'updatedHandle',
        'oldHandle' => 'originalHandle',
        'types' => [
            'craft-fs-Local' => [
                'path' => '@webroot/updated',
            ],
        ],
    ]);

    $response->assertOk();

    // Verify old handle doesn't exist
    $oldFs = Filesystems::getFilesystemByHandle('originalHandle');
    expect($oldFs)->toBeNull();

    // Verify new handle exists
    $newFs = Filesystems::getFilesystemByHandle('updatedHandle');
    expect($newFs)->not()->toBeNull();
    expect($newFs->name)->toBe('Updated Name');
});

test('save returns failure on invalid data', function () {
    $response = postJson(action([FilesystemsController::class, 'save']), [
        'type' => 'craft\fs\Local',
        'name' => '', // Empty name should fail
        'handle' => '',
        'types' => [
            'craft-fs-Local' => [],
        ],
    ]);

    $response->assertStatus(400);
});

test('delete removes filesystem successfully', function () {
    // Create a filesystem to delete
    $fs = Filesystems::createFilesystem([
        'type' => 'craft\fs\Local',
        'name' => 'To Delete',
        'handle' => 'toDelete',
        'settings' => [
            'path' => '@webroot/to-delete',
        ],
    ]);

    Filesystems::saveFilesystem($fs);

    // Delete it
    $response = deleteJson(action([FilesystemsController::class, 'destroy'], [$fs->handle]));

    $response->assertOk();

    // Verify it's deleted
    $deletedFs = Filesystems::getFilesystemByHandle('toDelete');
    expect($deletedFs)->toBeNull();
});

test('delete handles non-existent filesystem gracefully', function () {
    $response = deleteJson(action([FilesystemsController::class, 'destroy'], ['non-existent-filesystem']));

    $response->assertOk();
});

test('respects read-only mode for save operation', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([FilesystemsController::class, 'save']), [
        'type' => 'craft\fs\Local',
        'name' => 'Test',
        'handle' => 'test',
        'types' => [],
    ])
        ->assertForbidden();
});

test('respects read-only mode for delete operation', function () {
    Cms::config()->allowAdminChanges = false;

    deleteJson(action([FilesystemsController::class, 'destroy'], ['test']))
        ->assertForbidden();
});
