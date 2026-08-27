<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use LogicException;
use UnexpectedValueException;

class ActivityMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $versionEventId)
    {
        $this->queue = Cms::config()->queueName;
    }

    /** @return class-string[] */
    public function via(mixed $notifiable): array
    {
        return [MailChannel::class];
    }

    public function shouldSend(CraftUser $notifiable, string $channel): bool
    {
        $recipient = User::find()
            ->id($notifiable->getCraftUserId())
            ->status(User::STATUS_ACTIVE)
            ->one();
        $subject = $this->subject();

        return $recipient !== null
            && $subject !== null
            && Gate::forUser($recipient)->allows('view', $subject);
    }

    public function toMail(CraftUser $notifiable): SystemMessageMailable
    {
        $version = $this->version();
        $subject = $this->subject($version);
        $editUrl = $subject?->getCpEditUrl();

        if ($subject === null || $editUrl === null) {
            throw new LogicException('Activity mention notification subjects must have a control panel edit URL.');
        }

        $mailable = app(SystemMessages::class)->mailable(
            key: 'comment_mention',
            user: $notifiable->asElement(),
            variables: [
                'author' => $version->data['author']['label'],
                'subject' => $version->snapshots['subject']['label'],
                'comment' => $this->notificationComment($version),
                'link' => Template::raw(Url::cpUrl($editUrl)),
            ],
        );
        $mailable->siteId = $version->siteId;

        return $mailable;
    }

    private function version(): ActivityEvent
    {
        return ActivityEvent::query()->findOrFail($this->versionEventId);
    }

    private function subject(?ActivityEvent $version = null): ?ElementInterface
    {
        $version ??= $this->version();

        if ($version->subjectId === null) {
            return null;
        }

        return Elements::getElementByUid(
            $version->subjectId,
            $version->subjectType,
            $version->siteId,
        );
    }

    private function notificationComment(ActivityEvent $version): string
    {
        $mentionData = $version->data['mentions'] ?? [];

        if (! is_array($mentionData)) {
            throw new UnexpectedValueException('Activity comment mentions must be an array.');
        }

        $mentions = collect($mentionData)->keyBy('id');

        return preg_replace_callback(
            '/\[((?:\\\\.|[^]\\\\])*)]\(craft-user:(\d+)\)/',
            fn (array $match): string => isset($mentions[$match[2]])
                ? "@{$mentions[$match[2]]['username']}"
                : $match[0],
            $version->data['markdown'],
        ) ?? $version->data['markdown'];
    }
}
