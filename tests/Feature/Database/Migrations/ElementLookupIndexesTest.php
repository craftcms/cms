<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('preserves the SQLite URI expression index through installation', function () {
    $columns = DB::select('PRAGMA index_info("sites_uri_siteid_index")');
    $plan = DB::select('EXPLAIN QUERY PLAN SELECT "elementId" FROM "elements_sites" WHERE lower("uri") = ? AND "siteId" = ?', ['missing-page', 1]);

    expect(array_column($columns, 'cid'))->toBe([-2, 2])
        ->and($plan[0]->detail)->toContain('sites_uri_siteid_index');
})->skip(fn () => ! DB::isSqlite(), 'SQLite expression index regression.');
