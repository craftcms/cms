<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleBulkOpData;
use Illuminate\Support\Facades\DB;

it('deletes stale bulk op data', function (string $table, array $attributes) {
    DB::table($table)->insert(array_merge($attributes, [
        'key' => 'op1',
        'timestamp' => now(),
    ]));

    DB::table($table)->insert(array_merge($attributes, [
        'key' => 'op2',
        'timestamp' => now()->subWeeks(2)->subSecond(),
    ]));

    expect(DB::table($table)->count())->toBe(2);

    app(DeleteStaleBulkOpData::class)();

    expect(DB::table($table)->count())->toBe(1);
})->with([
    [Table::BULKOPEVENTS, [
        'senderClass' => 'foo',
        'eventName' => 'foo',
    ]],
    [Table::ELEMENTS_BULKOPS, [
        'elementId' => 1,
    ]],
]);
