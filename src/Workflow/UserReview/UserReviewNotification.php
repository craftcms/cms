<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Channels\MailChannel;

class UserReviewNotification extends CpNotification implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $messageKey,
        string $title,
        string $message,
        public readonly string $workflowName,
        public readonly string $entryTitle,
        public readonly string $entryUrl,
        public readonly ?string $stageName = null,
        public readonly ?string $note = null,
    ) {
        parent::__construct($message);

        $this->queue = Cms::config()->queueName;
        $this
            ->title($title)
            ->icon('clipboard-list-check')
            ->url($entryUrl);
    }

    /** @return class-string[] */
    #[\Override]
    public function via(CraftUser $notifiable): array
    {
        return [...parent::via($notifiable), MailChannel::class];
    }

    public function toMail(CraftUser $notifiable): SystemMessageMailable
    {
        return app(SystemMessages::class)->mailable(
            key: $this->messageKey,
            user: $notifiable->asElement(),
            variables: [
                'workflow' => $this->workflowName,
                'entry' => $this->entryTitle,
                'stage' => $this->stageName,
                'note' => $this->note,
                'link' => Template::raw($this->entryUrl),
            ],
        );
    }
}
