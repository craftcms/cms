<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use Craft\Cms\Announcement\Announcements;
use Craft\Cms\Announcement\Models\Announcement;
use Craft\Cms\User\Models\User;

it('can get announcements', function () {
    $service = new Announcements();

    expect($service->get())->toBe([]);
});

it('can get announcements for a user', function () {
    $service = new Announcements();

    $user = User::first();

    $announcement = Announcement::factory()
        ->unread()
        ->create([
            'userId' => $user->id,
        ]);

    $this->actingAs(User::find($user->id));

    expect($announcements = $service->get())->not()->toBe([])
        ->and($announcements[0]['id'])->toBe($announcement->id)
        ->and($announcements[0]['label'])->toBe('Craft CMS');
});

it('can mark announcements as read', function () {
    $service = new Announcements();

    $user = User::first();

    $announcement = Announcement::factory()
        ->unread()
        ->create([
            'userId' => $user->id,
        ]);

    $this->actingAs(User::find($user->id));

    $service->markAsRead([$announcement->id]);

    $announcement->refresh();

    expect($announcement->unread)->toBe(false)
        ->and($announcement->dateRead)->not()->toBeNull();
});
