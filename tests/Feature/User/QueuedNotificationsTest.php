<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Override;

test('queued notifications can be sent to user elements', function () {
    config()->set('queue.default', 'database');
    config()->set('auth.providers.users.model', QueuedNotificationUser::class);
    Auth::forgetGuards();

    $user = QueuedNotificationUser::query()->firstOrFail();
    $userElement = $user->asElement();
    $userElement->getAddressManager();

    Notification::send($userElement, new QueuedUserNotification);

    $job = Queue::pop();
    $job->fire();

    expect($user->notifications()->sole()->data)->toMatchArray([
        'notifiable' => User::class,
    ]);
});

class QueuedUserNotification extends LaravelNotification implements ShouldQueue
{
    use Queueable;

    /** @return class-string[] */
    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    /** @return array{notifiable: class-string} */
    public function toDatabase(object $notifiable): array
    {
        return ['notifiable' => $notifiable::class];
    }
}

class QueuedNotificationUser extends UserModel
{
    #[Override]
    protected $table = Table::USERS;
}
