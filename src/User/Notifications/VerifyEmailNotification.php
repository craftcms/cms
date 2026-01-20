<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use Craft;
use craft\helpers\Template;
use CraftCms\Cms\Notifications\Channels\CraftChannel;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        return [CraftChannel::class];
    }

    public function toCraft(User $user): bool
    {
        $url = Users::getEmailVerifyUrl($user, $this->token);
        $key = $user->pending ? 'account_activation' : 'verify_new_email';

        return Craft::$app->getMailer()
            ->composeFromKey($key, ['link' => Template::raw($url)])
            ->setTo($user)
            ->send();
    }
}
