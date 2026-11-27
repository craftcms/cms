<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\CriticalNotification\Checks\CriticalUpdateCheck;
use CraftCms\Cms\CriticalNotification\Checks\DebugInProductionCheck;
use CraftCms\Cms\CriticalNotification\CriticalNotification;
use CraftCms\Cms\CriticalNotification\CriticalNotifications;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Edition::set(Edition::Pro);
    $this->service = app(CriticalNotifications::class);
    $this->admin = User::find()->admin()->one();
});

afterEach(function () {
    CpNotification::viaUsing(null);
    CpNotification::flushMacros();
});

function criticalNotification(string $message = 'Something is wrong.'): CriticalNotification
{
    return new CriticalNotification('test-problem', 'Problem', $message);
}

/** @return list<string> */
function unreadCriticalMessages(User $user): array
{
    return $user->notifications()->whereNull('read_at')->get()
        ->filter(fn ($row): bool => $row->data['kind'] === CriticalNotification::KIND)
        ->map(fn ($row): string => $row->data['message'])
        ->values()
        ->all();
}

it('sends to users with the permission, including admins', function () {
    Notification::fake();
    $permitted = UserModel::factory()
        ->withPermissions(['receiveCriticalNotifications'])
        ->createElement(['admin' => false, 'username' => 'permitted']);
    $other = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement(['admin' => false, 'username' => 'other']);

    $this->service->send(criticalNotification());

    Notification::assertSentTo($this->admin, CriticalNotification::class);
    Notification::assertSentTo($permitted, CriticalNotification::class);
    Notification::assertNotSentTo($other, CriticalNotification::class);
});

it('always includes the database channel for critical notifications', function () {
    $notifiable = UserModel::query()->findOrFail($this->admin->id);

    expect(criticalNotification()->via($notifiable))->toBe([DatabaseChannel::class, MailChannel::class]);

    CpNotification::viaUsing(fn (CpNotification $notification, CraftUser $notifiable) => ['mail']);

    expect(criticalNotification()->via($notifiable))->toBe([DatabaseChannel::class, 'mail'])
        ->and(new CpNotification('Hi')->via($notifiable))->toBe(['mail']);
});

it('delivers through a channel a project adds with viaUsing and a macro', function () {
    $sent = new ArrayObject;
    Notification::extend('test', fn () => new class($sent)
    {
        public function __construct(private ArrayObject $sent) {}

        public function send(object $notifiable, CpNotification $notification): void
        {
            $this->sent[] = $notification->toTest($notifiable);
        }
    });
    CpNotification::viaUsing(fn (CpNotification $notification, CraftUser $notifiable) => match ($notification->kind) {
        CriticalNotification::KIND => ['test'],
        default => $notification->defaultChannels($notifiable),
    });
    CpNotification::macro('toTest', fn (CraftUser $notifiable): string => $this->toDatabase($notifiable)['message']);

    $this->service->send(criticalNotification('Sent to the test channel.'));

    expect($sent->getArrayCopy())->toBe(['Sent to the test channel.'])
        ->and(unreadCriticalMessages($this->admin))->toBe(['Sent to the test channel.']);
});

it('skips repeats until the details change or the problem is resolved', function () {
    CpNotification::viaUsing(fn () => ['database']);

    expect($this->service->send(criticalNotification('First')))->toBeTrue()
        ->and($this->service->send(criticalNotification('First')))->toBeFalse()
        ->and(unreadCriticalMessages($this->admin))->toBe(['First']);

    expect($this->service->send(criticalNotification('Changed')))->toBeTrue()
        ->and(unreadCriticalMessages($this->admin))->toBe(['Changed']);

    // Reading it doesn't bring it back right away
    $this->admin->notifications()->whereNull('read_at')->update(['read_at' => now()->toDateTimeString()]);
    expect($this->service->send(criticalNotification('Changed')))->toBeFalse();

    $this->service->resolve('test-problem');

    expect($this->service->send(criticalNotification('Changed')))->toBeTrue()
        ->and(unreadCriticalMessages($this->admin))->toBe(['Changed']);
});

it('sends a reminder 7 days after it was read', function () {
    CpNotification::viaUsing(fn () => ['database']);
    $this->service->send(criticalNotification());
    $this->admin->notifications()->update(['read_at' => now()->subDays(8)->toDateTimeString()]);

    expect($this->service->send(criticalNotification()))->toBeTrue();
});

it('reports critical updates and resolves them once installed', function () {
    CpNotification::viaUsing(fn () => ['database']);
    $check = app(CriticalUpdateCheck::class);
    $endpoint = Api::craftApiEndpoint().'/updates';

    Http::fake([$endpoint => Http::sequence()
        ->push(['cms' => ['releases' => [['version' => '6.0.1', 'critical' => true], ['version' => '6.0.0']]], 'plugins' => []])
        ->pushResponse(new ConnectionException('Offline'))
        ->push(['cms' => ['releases' => [['version' => '6.0.2']]], 'plugins' => []]),
    ]);

    $notification = $check->run();
    expect($notification->toDatabase(UserModel::query()->findOrFail($this->admin->id))['message'])
        ->toContain('Craft CMS 6.0.1')
        ->not->toContain('6.0.0');
    $this->service->send($notification);

    // A failed fetch can’t tell, so it doesn’t resolve the open notification
    expect(fn () => $check->run())->toThrow(RuntimeException::class);

    expect($check->run())->toBeNull();
});

it('warns about debug mode only in production', function () {
    config()->set('app.debug', true);
    $environment = app()->environment();

    try {
        app()->instance('env', 'production');
        expect(app(DebugInProductionCheck::class)->run())->toBeInstanceOf(CriticalNotification::class);

        app()->instance('env', 'staging');
        expect(app(DebugInProductionCheck::class)->run())->toBeNull();

        app()->instance('env', 'production');
        config()->set('app.debug', false);
        expect(app(DebugInProductionCheck::class)->run())->toBeNull();
    } finally {
        app()->instance('env', $environment);
    }
});
