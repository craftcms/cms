<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\User;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\Controllers\Auth\VerifyEmailController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Override;
use ReflectionProperty;

use function Pest\Laravel\post;

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

test('queued verification keeps account identity and sends only to the pending address', function (bool $replacePending) {
    config()->set('queue.default', 'database');
    $mail = null;
    $channel = Mockery::mock(MailChannel::class);
    $channel->shouldReceive('send')->once()->andReturnUsing(function ($user, $notification) use (&$mail) {
        $mail = $notification->toMail($user);
    });
    app()->instance(MailChannel::class, $channel);
    $user = UserModel::factory()->createElement(['email' => 'original@example.com']);
    $user->unverifiedEmail = 'pending@example.com';
    new ReflectionProperty(User::class, 'sendVerificationEmailAfterRequest')->setValue($user, true);
    app(Elements::class)->saveElement($user, false);

    $job = Queue::pop();
    if ($replacePending) {
        $user->unverifiedEmail = 'latest@example.com';
        app(Elements::class)->saveElement($user, false);
        $job->fire();
        $job = Queue::pop();
    }
    $job->fire();
    parse_str(parse_url((string) $mail->variables['link'], PHP_URL_QUERY), $query);

    expect($mail->to)->toBe([['name' => $user->fullName, 'address' => $user->unverifiedEmail]]);
    expect($user->email)->toBe('original@example.com');
    expect(Password::broker()->tokenExists($user, $query['code']))->toBeTrue();
    post(action([VerifyEmailController::class, 'store']), $query)->assertRedirect();
    expect(UserModel::query()->find($user->id)->email)->toBe($user->unverifiedEmail);
})->with([false, true]);

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
