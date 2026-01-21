<?php

declare(strict_types=1);

namespace CraftCms\Cms\Notifications\Channels;

use Illuminate\Notifications\Notification;

class CraftChannel
{
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notification, 'toCraft')) {
            return null;
        }

        return $notification->toCraft($notifiable);
    }
}
