<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

it('moves the given nested elements to the offset', function () {
    $owner = User::findOne();
    $first = AddressModel::factory()->withOwnedElement($owner, 1)->createElement();
    $second = AddressModel::factory()->withOwnedElement($owner, 2)->createElement();
    $third = AddressModel::factory()->withOwnedElement($owner, 3)->createElement();
    $owner = User::findOne();

    Elements::reorderNestedElements($owner, $owner->getAddresses(), [$first->id], 2);

    expect(DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $owner->id)
        ->orderBy('sortOrder')
        ->pluck('sortOrder', 'elementId')
        ->map(fn (mixed $sortOrder): int => (int) $sortOrder)
        ->all()
    )->toBe([
        $second->id => 1,
        $third->id => 2,
        $first->id => 3,
    ]);
});
