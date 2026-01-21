<?php

use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\Http\Controllers\AnnouncementsController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('markRead', function () {
    $announcement = Announcement::factory()
        ->unread()
        ->create([
            'userId' => auth()->user()->id,
        ]);

    expect($announcement->fresh()->unread)->toBeTrue();

    postJson(action([AnnouncementsController::class, 'markRead']), [
        'ids' => [$announcement->id],
    ])->assertOk();

    expect($announcement->fresh()->unread)->toBeFalse();
});

test('ids must be an array of integers', function () {
    postJson(action([AnnouncementsController::class, 'markRead']), [
        'ids' => 'foo',
    ])->assertJsonValidationErrorFor('ids');

    postJson(action([AnnouncementsController::class, 'markRead']), [
        'ids' => ['foo'],
    ])->assertJsonValidationErrorFor('ids.0');
});
