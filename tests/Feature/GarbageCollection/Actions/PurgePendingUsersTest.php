<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\PurgePendingUsers;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

it('purges pending users with stale activation codes', function () {
    $this->markTestSkipped('Currently causes lock timeouts');

    Cms::config()->purgePendingUsersDuration = 60 * 60 * 24;

    $user1Element = Element::factory()->create([
        'type' => User::class,
        'dateDeleted' => null,
    ]);
    DB::table(Table::ELEMENTS_SITES)->insert([
        'elementId' => $user1Element->id,
        'siteId' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    CraftCms\Cms\User\Models\User::factory()->create([
        'id' => $user1Element->id,
        'pending' => true,
        'verificationCodeIssuedDate' => now(),
    ]);

    $user2Element = Element::factory()->create([
        'type' => User::class,
        'dateDeleted' => null,
    ]);
    DB::table(Table::ELEMENTS_SITES)->insert([
        'elementId' => $user2Element->id,
        'siteId' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    CraftCms\Cms\User\Models\User::factory()->create([
        'id' => $user2Element->id,
        'pending' => true,
        'verificationCodeIssuedDate' => now()->subWeek()->format('Y-m-d H:i:s'),
    ]);

    app(PurgePendingUsers::class)();

    expect($user1Element->fresh()->dateDeleted)->toBeNull();
    expect($user2Element->fresh()->dateDeleted)->not()->toBeNull();
});
