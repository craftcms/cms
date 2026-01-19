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

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [CraftChannel::class];
    }

    public function toCraft(User $user): bool
    {
        $url = Users::getPasswordResetUrl($user);

        return Craft::$app->getMailer()
            ->composeFromKey('forgot_password', ['link' => Template::raw($url)])
            ->setTo($user)
            ->send();
    }
}
