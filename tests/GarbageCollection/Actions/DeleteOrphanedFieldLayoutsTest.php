<?php

use craft\elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;

it('deletes orphaned data', function () {
    $fieldLayoutId = DB::table(Table::FIELDLAYOUTS)->insertGetId([
        'type' => Asset::class,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    DB::table(Table::VOLUMES)->insertGetId([
        'fieldLayoutId' => $fieldLayoutId,
        'name' => 'Test',
        'handle' => 'test',
        'fs' => 'local',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    expect(DB::table(Table::FIELDLAYOUTS)->count())->toBe(1);

    app(DeleteOrphanedFieldLayouts::class, [
        'elementType' => Asset::class,
        'table' => Table::VOLUMES,
    ])();

    expect(DB::table(Table::FIELDLAYOUTS)->count())->toBe(1);

    DB::table(Table::VOLUMES)->update([
        'fieldLayoutId' => null,
    ]);

    app(DeleteOrphanedFieldLayouts::class, [
        'elementType' => Asset::class,
        'table' => Table::VOLUMES,
    ])();

    expect(DB::table(Table::FIELDLAYOUTS)->count())->toBe(0);
});
