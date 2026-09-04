<?php

declare(strict_types=1);

use craft\services\Announcements;
use CraftCms\Cms\Cp\Data\NotificationData;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Cp\Notifications\NotificationCenter;

it('returns announcement notifications in the legacy shape', function() {
    $notifications = Mockery::mock(NotificationCenter::class);
    $notifications->shouldReceive('get')->once()->andReturn([
        notificationData('announcement', Announcements::KIND),
        notificationData('notification', CpNotification::class),
    ]);

    app()->instance(NotificationCenter::class, $notifications);

    expect(new Announcements()->get())->toBe([[
        'id' => 'announcement',
        'icon' => false,
        'label' => 'Craft CMS',
        'heading' => 'Title',
        'body' => 'Message',
        'unread' => true,
    ]]);
});

it('only marks announcement notifications as read', function() {
    $notifications = Mockery::mock(NotificationCenter::class);
    $notifications->shouldReceive('get')->once()->andReturn([
        notificationData('announcement', Announcements::KIND),
        notificationData('notification', CpNotification::class),
    ]);
    $notifications->shouldReceive('markAsRead')->once()->with(['announcement']);

    app()->instance(NotificationCenter::class, $notifications);

    new Announcements()->markAsRead(['announcement', 'notification']);
});

function notificationData(string $id, string $kind): NotificationData
{
    return new NotificationData(
        id: $id,
        kind: $kind,
        title: 'Title',
        messageHtml: 'Message',
        byline: null,
        icon: null,
        image: null,
        imageAlt: null,
        url: null,
        buttons: [],
        createdAt: now()->toIso8601String(),
        createdAtLabel: 'now',
        unread: true,
    );
}
