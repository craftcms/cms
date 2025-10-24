<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteElements;

it('hard deletes soft deleted elements', function () {
    // Active
    Element::factory()->create(['dateDeleted' => null]);
    // Recently deleted
    Element::factory()->create(['dateDeleted' => now()]);
    // Old deleted
    Element::factory()->create(['dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1)]);

    $currentCount = DB::table(Table::ELEMENTS)->count();

    app(HardDeleteElements::class)();

    expect(DB::table(Table::ELEMENTS)->count())->toBe($currentCount - 1);
});
