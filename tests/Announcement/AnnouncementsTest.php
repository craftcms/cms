<?php

declare(strict_types=1);

use craft\elements\User;
use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\Support\Facades\Announcements;

use function Pest\Laravel\actingAs;

it('can get announcements', function () {
    expect(Announcements::get())->toBe([]);
});

it('can get announcements for a user', function () {
    $user = User::find();

    $announcement = Announcement::factory()
        ->unread()
        ->create([
            'userId' => $user->id,
        ]);

    actingAs($user);

    expect($announcements = Announcements::get())->not()->toBe([])
        ->and($announcements[0]['id'])->toBe($announcement->id)
        ->and($announcements[0]['label'])->toBe('Craft CMS');
});

it('can mark announcements as read', function () {
    $user = User::first();

    $announcement = Announcement::factory()
        ->unread()
        ->create([
            'userId' => $user->id,
        ]);

    actingAs($user);

    Announcements::markAsRead([$announcement->id]);

    $announcement->refresh();

    expect($announcement->unread)->toBe(false)
        ->and($announcement->dateRead)->not()->toBeNull();
});
