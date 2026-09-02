<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Data\NotificationButtonData;
use CraftCms\Cms\Cp\Enums\ButtonVariant;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Cp\Notifications\NotificationCenter;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travel;

it('shows unread and recently read notifications for the current user', function () {
    $user = User::query()->firstOrFail();
    actingAs($user);

    $user->notify(new CpNotification('Hello <script>alert(1)</script> **world**')
        ->title('A title')
        ->byline('Editorial team')
        ->image(url: 'https://example.com/avatar.jpg', alt: '')
        ->buttons([new NotificationButtonData('Open', '/admin', variant: ButtonVariant::Primary)]));
    travel(1)->seconds();
    $user->notify(new CpNotification('Recently read')->title('Recently read title'));
    $recentlyRead = $user->notifications()->get()
        ->first(fn ($notification): bool => $notification->data['message'] === 'Recently read');
    $recentlyRead->markAsRead();
    $user->notify(new CpNotification('Too old')->title('Too old title'));
    $tooOld = $user->notifications()->get()
        ->first(fn ($notification): bool => $notification->data['message'] === 'Too old');
    $tooOld->forceFill(['read_at' => now()->subDays(8)])->save();

    $notifications = app(NotificationCenter::class)->get();
    $recentlyReadNotification = collect($notifications)
        ->firstWhere('title', 'Recently read title');
    $unreadNotification = collect($notifications)
        ->firstWhere('title', 'A title');

    expect($notifications)->toHaveCount(2)
        ->and(collect($notifications)->pluck('title')->all())->toBe(['Recently read title', 'A title'])
        ->and($recentlyReadNotification->unread)->toBeFalse()
        ->and($unreadNotification->byline)->toBe('Editorial team')
        ->and($unreadNotification->image)->toBe('https://example.com/avatar.jpg')
        ->and($unreadNotification->imageAlt)->toBe('')
        ->and($unreadNotification->messageHtml)->toContain('&lt;script&gt;')
        ->and($unreadNotification->messageHtml)->toContain('<strong>world</strong>')
        ->and($unreadNotification->buttons[0]->variant)->toBe(ButtonVariant::Primary);
});

it('uses notifications from the configured auth model', function () {
    $user = ConfiguredNotificationUser::query()->firstOrFail();
    actingAs($user);
    $user->notify(new CpNotification('Custom auth model')->title('Custom model notification'));
    $notifications = app(NotificationCenter::class)->get();

    expect($notifications)
        ->toHaveCount(1)
        ->and($notifications[0]->title)->toBe('Custom model notification');
});

it('does not require the current Craft user to be an Eloquent model', function () {
    actingAs(Mockery::mock(CraftUser::class));

    expect(app(NotificationCenter::class)->get())->toBe([]);
});

class ConfiguredNotificationUser extends User
{
    #[Override]
    protected $table = Table::USERS;
}
