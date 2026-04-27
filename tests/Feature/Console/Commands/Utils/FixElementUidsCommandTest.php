<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;

it('reports when no duplicate element uids exist', function () {
    $this->artisan('craft:utils:fix-element-uids')
        ->expectsOutputToContain('No elements with duplicate UIDs found.')
        ->assertSuccessful();
});

it('reassigns duplicate element uids while keeping the first matching row unchanged', function () {
    $firstElement = ElementModel::factory()->create();
    $secondElement = ElementModel::factory()->create();
    $thirdElement = ElementModel::factory()->create();
    $duplicateUid = (string) Str::uuid();

    DB::table(Table::ELEMENTS)
        ->whereIn('id', [$firstElement->id, $secondElement->id, $thirdElement->id])
        ->update(['uid' => $duplicateUid]);

    $this->artisan('utils/fix-element-uids')
        ->expectsOutputToContain('Found 3 elements with duplicate UIDs.')
        ->expectsOutputToContain("Changing $duplicateUid ({$secondElement->id})")
        ->expectsOutputToContain("Changing $duplicateUid ({$thirdElement->id})")
        ->expectsOutputToContain('Finished assigning unique UIDs to all elements.')
        ->assertSuccessful();

    $uidsById = DB::table(Table::ELEMENTS)
        ->whereIn('id', [$firstElement->id, $secondElement->id, $thirdElement->id])
        ->orderBy('id')
        ->pluck('uid', 'id');

    expect($uidsById[$firstElement->id])->toBe($duplicateUid)
        ->and($uidsById[$secondElement->id])->not->toBe($duplicateUid)
        ->and($uidsById[$thirdElement->id])->not->toBe($duplicateUid)
        ->and($uidsById[$secondElement->id])->not->toBe($uidsById[$thirdElement->id]);

    expect(DB::table(Table::ELEMENTS)
        ->whereIn('id', [$firstElement->id, $secondElement->id, $thirdElement->id])
        ->distinct()
        ->count('uid'))
        ->toBe(3);
});
