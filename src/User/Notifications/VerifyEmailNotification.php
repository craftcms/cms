<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use SensitiveParameter;

/**
 * @since 6.0.0
 */
class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The email verification URL, built for the request that sent the notification.
     */
    public string $url;

    /**
     * The ID of the site the message is sent from.
     */
    public int $siteId;

    public function __construct(
        #[SensitiveParameter]
        public string $token,
        CraftUser $user,
        public ?string $recipient = null,
    ) {
        $this->queue = Cms::config()->queue->name;

        // The message is sent from a queue worker, which has no request or current site of its own
        $user = $user->asElement();
        $this->url = Users::getEmailVerifyUrl($user, $token);
        $this->siteId = $this->mailable($user)->siteId ?? Sites::getCurrentSite()->id;
    }

    /** @return class-string[] */
    public function via(mixed $notifiable): array
    {
        return [MailChannel::class];
    }

    public function shouldSend(CraftUser $user, string $channel): bool
    {
        return (($this->recipient ?? null) === null || $this->recipient === $user->getEmailForVerification())
            && Password::tokenExists($user, $this->token);
    }

    public function toMail(CraftUser $user): SystemMessageMailable
    {
        $user = $user->asElement();

        $mailable = $this->mailable($user);
        $mailable->siteId = $this->siteId;
        $mailable->to = [];

        return $mailable->to($this->recipient ?? $user->getEmailForVerification(), $user->fullName);
    }

    private function mailable(User $user): SystemMessageMailable
    {
        return app(SystemMessages::class)->mailable(
            key: 'verify_new_email',
            user: $user,
            variables: ['link' => Template::raw($this->url)],
        );
    }
}
