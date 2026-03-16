<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use craft\helpers\Template;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [MailChannel::class];
    }

    public function toMail(User $user): SystemMessageMailable
    {
        $url = Users::getPasswordResetUrl($user);

        return app(SystemMessages::class)->mailable(
            key: 'forgot_password',
            user: $user,
            variables: ['link' => Template::raw($url)],
        );
    }
}
