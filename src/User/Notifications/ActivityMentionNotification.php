<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\Activity\ActivityComments;
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
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use LogicException;

class ActivityMentionNotification extends Notification implements ShouldQueueAfterCommit
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
            && app(ActivityComments::class)->canMention($recipient, $subject);
    }

    public function toMail(CraftUser $notifiable): SystemMessageMailable
    {
        $version = $this->version();
        $subject = $this->subject($version);
        $editUrl = $subject?->getCpEditUrl();

        if ($subject === null || $editUrl === null) {
            throw new LogicException('Activity mention notification subjects must have a control panel edit URL.');
        }

        $recipient = $notifiable->asElement();
        $mailable = app(SystemMessages::class)->mailable(
            key: 'comment_mention',
            user: $recipient,
            variables: [
                'author' => $version->data['author']['label'],
                'subject' => $version->snapshots['subject']['label'],
                'comment' => app(ActivityComments::class)->notificationText($version, $recipient),
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
}
