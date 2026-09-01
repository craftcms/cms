<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class ActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {
        $this->queue = Cms::config()->queueName;
    }

    /** @return class-string[] */
    public function via(mixed $notifiable): array
    {
        return [MailChannel::class];
    }

    public function toMail(CraftUser $user): SystemMessageMailable
    {
        $user = $user->asElement();

        $url = Users::getActivationUrl($user, $this->token);

        return app(SystemMessages::class)->mailable(
            key: 'account_activation',
            user: $user,
            variables: ['link' => Template::raw($url)],
        );
    }
}
