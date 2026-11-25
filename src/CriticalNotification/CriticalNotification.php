<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;

/**
 * A problem with the site that someone needs to act on, such as a critical update.
 *
 * Send it with {@see CriticalNotifications::send()}, which picks the recipients and skips repeats.
 * It always appears in the control panel, whatever channels {@see CpNotification::viaUsing()} returns.
 *
 * @since 6.0.0
 */
class CriticalNotification extends CpNotification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const string KIND = 'critical';

    public int $tries = 3;

    /** Identifies this version of the problem, so it’s sent again when the details change. */
    public readonly string $fingerprint;

    /**
     * @param  string  $key  Identifies the problem, such as `critical-update`
     */
    public function __construct(
        public readonly string $key,
        string $title,
        string $message,
        ?string $url = null,
    ) {
        parent::__construct($message);

        $this->fingerprint = hash('xxh128', "$title\n$message");
        $this->queue = Cms::config()->queueName;
        $this
            ->kind(self::KIND)
            ->title($title)
            ->icon('triangle-exclamation')
            ->url($url);
    }

    /** @return list<string> */
    #[\Override]
    public function via(CraftUser $notifiable): array
    {
        $channels = parent::via($notifiable);

        if (! in_array(DatabaseChannel::class, $channels, true) && ! in_array('database', $channels, true)) {
            array_unshift($channels, DatabaseChannel::class);
        }

        return $channels;
    }

    /** @return list<string> */
    #[\Override]
    public function defaultChannels(CraftUser $notifiable): array
    {
        return [...parent::defaultChannels($notifiable), MailChannel::class];
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function toDatabase(CraftUser $notifiable): array
    {
        return [
            ...parent::toDatabase($notifiable),
            'key' => $this->key,
            'fingerprint' => $this->fingerprint,
        ];
    }
}
