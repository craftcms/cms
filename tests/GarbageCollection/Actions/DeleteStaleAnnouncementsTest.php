<?php

declare(strict_types=1);

use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleAnnouncements;
use CraftCms\Cms\User\Models\User;

it('deletes stale announcements', function () {
    // Valid announcement
    Announcement::factory()->create([
        'userId' => User::first()->id,
        'dateRead' => now(),
    ]);

    // Stale announcement
    Announcement::factory()->create([
        'userId' => User::first()->id,
        'dateRead' => now()->subDays(7)->subSecond(),
    ]);

    expect(Announcement::count())->toBe(2);

    resolve(DeleteStaleAnnouncements::class)();

    expect(Announcement::count())->toBe(1);
});
