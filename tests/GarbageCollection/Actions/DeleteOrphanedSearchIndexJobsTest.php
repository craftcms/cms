<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedSearchIndexJobs;
use Illuminate\Support\Facades\DB;

it('deletes orphaned search index jobs', function () {
    $element = Element::factory()->create();

    // Valid, not deleted
    DB::table(Table::SEARCHINDEXQUEUE)->insert([
        'elementId' => $element->id,
        'siteId' => 1,
        'reserved' => false,
    ]);

    // Invalid, deleted
    DB::table(Table::SEARCHINDEXQUEUE)->insert([
        'elementId' => 999,
        'siteId' => 1,
        'reserved' => false,
    ]);

    expect(DB::table(Table::SEARCHINDEXQUEUE)->count())->toBe(2);

    app(DeleteOrphanedSearchIndexJobs::class)();

    expect(DB::table(Table::SEARCHINDEXQUEUE)->count())->toBe(1);
});
