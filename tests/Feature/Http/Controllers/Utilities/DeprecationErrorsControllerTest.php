<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access deprecation errors utility', function () {
    Cms::config()->disabledUtilities = [DeprecationErrors::id()];

    $logId = DB::table('deprecationerrors')->insertGetId([
        'key' => 'test-deprecation',
        'message' => 'Test deprecation message',
        'fingerprint' => md5('test-deprecation-auth'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    getJson(action([DeprecationErrorsController::class, 'show'], ['logId' => $logId]))
        ->assertForbidden();
});

test('get deprecation error traces modal requires valid log id', function () {
    getJson(action([DeprecationErrorsController::class, 'show'], ['logId' => 10]))
        ->assertNotFound();
});

test('get deprecation error traces modal returns html', function () {
    $logId = DB::table('deprecationerrors')->insertGetId([
        'key' => 'test-deprecation',
        'message' => 'Test deprecation message',
        'fingerprint' => md5('test-deprecation'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    getJson(action([DeprecationErrorsController::class, 'show'], ['logId' => $logId]))
        ->assertOk()
        ->assertJsonStructure(['html']);
});

test('delete single deprecation error', function () {
    $logId = DB::table('deprecationerrors')->insertGetId([
        'key' => 'test-deprecation',
        'message' => 'Test deprecation message',
        'fingerprint' => md5('test-deprecation-delete'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    deleteJson(action([DeprecationErrorsController::class, 'destroy'], ['logId' => $logId]))
        ->assertOk();
});

test('delete all deprecation errors', function () {
    DB::table('deprecationerrors')->insert([
        'key' => 'test-deprecation',
        'message' => 'Test deprecation message',
        'fingerprint' => md5('test-deprecation-delete'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    DB::table('deprecationerrors')->insert([
        'key' => 'test-deprecation-2',
        'message' => 'Test deprecation message (2)',
        'fingerprint' => md5('test-deprecation-delete-2'),
        'lastOccurrence' => now(),
        'file' => __FILE__,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    $this->assertDatabaseCount('deprecationerrors', 2);

    deleteJson(action([DeprecationErrorsController::class, 'destroyAll']))
        ->assertOk();

    $this->assertDatabaseCount('deprecationerrors', 0);
});
