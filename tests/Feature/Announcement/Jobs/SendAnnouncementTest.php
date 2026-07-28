<?php

declare(strict_types=1);

use CraftCms\Cms\Announcement\Jobs\SendAnnouncement;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new SendAnnouncement(
        heading: 'Test Heading',
        body: 'Test Body',
    );

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with heading and body', function () {
    $job = new SendAnnouncement(
        heading: 'Announcement Heading',
        body: 'Announcement Body',
    );

    expect($job->heading)->toBe('Announcement Heading')
        ->and($job->body)->toBe('Announcement Body')
        ->and($job->pluginHandle)->toBeNull()
        ->and($job->adminsOnly)->toBeFalse();
});

it('can be instantiated with all parameters', function () {
    $job = new SendAnnouncement(
        heading: 'Plugin Update',
        body: 'New features available',
        pluginHandle: 'my-plugin',
        adminsOnly: true,
    );

    expect($job->heading)->toBe('Plugin Update')
        ->and($job->body)->toBe('New features available')
        ->and($job->pluginHandle)->toBe('my-plugin')
        ->and($job->adminsOnly)->toBeTrue();
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new SendAnnouncement(
        heading: 'Test',
        body: 'Body',
    );

    dispatch($job);

    Queue::assertPushed(SendAnnouncement::class);
});

it('provides a description', function () {
    $job = new SendAnnouncement(
        heading: 'Test Heading',
        body: 'Test Body',
    );

    $description = $job->getDescription();

    expect($description)->toContain('announcement');
});

it('creates announcements for users when executed', function () {
    $user = User::find()->one();

    $initialCount = DB::table(Table::ANNOUNCEMENTS)
        ->where('userId', $user->id)
        ->count();

    $job = new SendAnnouncement(
        heading: 'New Feature Available',
        body: 'Check out this awesome new feature!',
    );

    app()->call($job->handle(...));

    $newCount = DB::table(Table::ANNOUNCEMENTS)
        ->where('userId', $user->id)
        ->count();

    expect($newCount)->toBeGreaterThan($initialCount);

    $announcement = DB::table(Table::ANNOUNCEMENTS)
        ->where('userId', $user->id)
        ->where('heading', 'New Feature Available')
        ->first();

    expect($announcement)->not()->toBeNull()
        ->and($announcement->body)->toBe('Check out this awesome new feature!');
});

it('handles invalid plugin handle gracefully', function () {
    $initialCount = DB::table(Table::ANNOUNCEMENTS)->count();

    $job = new SendAnnouncement(
        heading: 'Plugin Announcement',
        body: 'From a plugin',
        pluginHandle: 'nonexistent-plugin-handle',
    );

    app()->call($job->handle(...));

    // Should not create any announcements with invalid plugin handle
    $newCount = DB::table(Table::ANNOUNCEMENTS)->count();

    expect($newCount)->toBe($initialCount);
});
