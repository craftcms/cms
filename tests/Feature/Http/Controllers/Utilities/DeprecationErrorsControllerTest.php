<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\DeprecationErrorsController;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

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

    postJson(action([DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']), [
        'logId' => $logId,
    ])
        ->assertForbidden();
});

test('get deprecation error traces modal requires log id', function () {
    postJson(action([DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']), [])
        ->assertJsonValidationErrors(['logId']);
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

    postJson(action([DeprecationErrorsController::class, 'getDeprecationErrorTracesModal']), [
        'logId' => $logId,
    ])
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

    postJson(action([DeprecationErrorsController::class, 'deleteDeprecationError']), [
        'logId' => $logId,
    ])
        ->assertOk();
});

test('delete all deprecation errors', function () {
    postJson(action([DeprecationErrorsController::class, 'deleteAllDeprecationErrors']))
        ->assertOk();
});
