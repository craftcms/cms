<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Http\Controllers\NotificationsController;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    actingAs($this->user);
});

it('marks selected notifications as read', function () {
    $this->user->notify(new CpNotification('Test Body')->title('Test Heading'));
    $notification = $this->user->unreadNotifications()->firstOrFail();

    postJson(action([NotificationsController::class, 'markRead']), [
        'ids' => [$notification->id],
    ])->assertOk();

    expect($notification->fresh()->read_at)->not()->toBeNull();
});

test('ids must be an array of UUIDs', function () {
    postJson(action([NotificationsController::class, 'markRead']), [
        'ids' => 'foo',
    ])->assertJsonValidationErrorFor('ids');

    postJson(action([NotificationsController::class, 'markRead']), [
        'ids' => ['foo'],
    ])->assertJsonValidationErrorFor('ids.0');
});
