<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Notifications;

use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Notifications\CpNotification;
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
use LogicException;

class ActivityMentionNotification extends CpNotification implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ActivityEvent $event)
    {
        parent::__construct(
            static fn (CraftUser $notifiable): string => app(ActivityComments::class)
                ->notificationText($event, $notifiable->asElement()),
        );

        $this->queue = Cms::config()->queueName;
        $this
            ->title('comment_mention_subject')
            ->byline($event->data['author']['label'])
            ->icon('comment')
            ->url($this->subject()?->getCpEditUrl());
    }

    /** @return class-string[] */
    #[\Override]
    public function via(CraftUser $notifiable): array
    {
        return [...parent::via($notifiable), MailChannel::class];
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
        $subject = $this->subject();
        $editUrl = $subject?->getCpEditUrl();

        if ($subject === null || $editUrl === null) {
            throw new LogicException('Activity mention notification subjects must have a control panel edit URL.');
        }

        $recipient = $notifiable->asElement();
        $mailable = app(SystemMessages::class)->mailable(
            key: 'comment_mention',
            user: $recipient,
            variables: [
                'author' => $this->event->data['author']['label'],
                'subject' => $this->event->snapshots['subject']['label'],
                'comment' => app(ActivityComments::class)->notificationText($this->event, $recipient),
                'link' => Template::raw(Url::cpUrl($editUrl)),
            ],
        );
        $mailable->siteId = $this->event->siteId;

        return $mailable;
    }

    private function subject(): ?ElementInterface
    {
        if ($this->event->subjectId === null) {
            return null;
        }

        return Elements::getElementByUid(
            $this->event->subjectId,
            $this->event->subjectType,
            $this->event->siteId,
        );
    }
}
