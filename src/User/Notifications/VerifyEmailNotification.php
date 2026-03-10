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
use SensitiveParameter;

final class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [MailChannel::class];
    }

    public function toMail(User $user): SystemMessageMailable
    {
        $url = Users::getEmailVerifyUrl($user, $this->token);
        $key = $user->pending ? 'account_activation' : 'verify_new_email';

        return app(SystemMessages::class)->mailable(
            key: $key,
            user: $user,
            variables: ['link' => Template::raw($url)],
        );
    }
}
