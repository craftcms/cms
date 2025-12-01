<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleElementActivity;
use Illuminate\Support\Facades\DB;

it('deletes stale activity', function () {
    DB::table(Table::ELEMENTACTIVITY)->insert([
        'elementId' => 1,
        'userId' => 1,
        'siteId' => 1,
        'type' => 'foo',
        'timestamp' => now(),
    ]);

    DB::table(Table::ELEMENTACTIVITY)->insert([
        'elementId' => 1,
        'userId' => 1,
        'siteId' => 1,
        'type' => 'foo-2',
        'timestamp' => now()->subMinute()->subSecond(),
    ]);

    expect(DB::table(Table::ELEMENTACTIVITY)->count())->toBe(2);

    app(DeleteStaleElementActivity::class)();

    expect(DB::table(Table::ELEMENTACTIVITY)->count())->toBe(1);
});
