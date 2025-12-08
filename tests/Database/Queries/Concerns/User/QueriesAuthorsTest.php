<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Elements\User;

it('can query users by having authored entries', function () {
    $entry = Entry::factory()->create();
    $entryElement = Craft::$app->getElements()->getElementById($entry->id);

    expect(userQuery()->authors()->count())->toBe(0);
    expect(userQuery()->authors(false)->count())->toBe(1);
    expect(userQuery()->authorOf($entryElement)->count())->toBe(0);

    \Illuminate\Support\Facades\DB::table(Table::ENTRIES_AUTHORS)
        ->insert([
            'authorId' => User::find()->one()->id,
            'entryId' => $entryElement->id,
            'sortOrder' => 1,
        ]);

    expect(userQuery()->authors()->count())->toBe(1);
    expect(userQuery()->authors(false)->count())->toBe(0);

    expect(userQuery()->authorOf($entryElement)->count())->toBe(1);
});
