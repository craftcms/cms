<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleNotifications;
use CraftCms\Cms\User\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

it('deletes control panel notifications read over a week ago', function () {
    $user = User::query()->firstOrFail();
    $notification = fn (string $type, ?DateTimeInterface $readAt) => DatabaseNotification::create([
        'id' => Str::uuid()->toString(),
        'type' => $type,
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'data' => [],
        'read_at' => $readAt,
    ]);

    $notification(CpNotification::TYPE, now()->subDays(7)->subSecond());
    $recentlyRead = $notification(CpNotification::TYPE, now()->subDays(6));
    $unread = $notification(CpNotification::TYPE, null);
    $otherType = $notification('other', now()->subDays(8));

    app(DeleteStaleNotifications::class)();

    expect(DatabaseNotification::query()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$recentlyRead->id, $unread->id, $otherType->id])->sort()->values()->all());
});
