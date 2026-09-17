<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\Workflow\Events\WorkflowCommented;
use CraftCms\Cms\Workflow\Events\WorkflowTransitioned;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

use function CraftCms\Cms\t;

class UserReviewServiceProvider extends ServiceProvider
{
    public function boot(SystemMessages $messages): void
    {
        Event::listen(WorkflowTransitioned::class, [SendUserReviewNotifications::class, 'handleTransition']);
        Event::listen(WorkflowCommented::class, [SendUserReviewNotifications::class, 'handleComment']);

        foreach ($this->messageDefinitions() as $key => [$heading, $subject, $body]) {
            $messages->register($key, fn () => new SystemMessage([
                'key' => $key,
                'heading' => $heading,
                'subject' => $subject,
                'body' => $body,
            ]));
        }
    }

    /** @return array<string, array{string, string, string}> */
    private function messageDefinitions(): array
    {
        return [
            'workflow_review_requested' => [t('When a workflow review is requested:'), t('Review requested for “{{entry}}”'), "{{entry}} is awaiting your approval in {{stage}}.\n\n<{{link}}>"],
            'workflow_review_commented' => [t('When a workflow review is commented on:'), t('New review comment on “{{entry}}”'), "A reviewer commented on {{entry}} during {{stage}}.\n\n{{note}}\n\n<{{link}}>"],
            'workflow_changes_requested' => [t('When changes are requested:'), t('Changes requested for “{{entry}}”'), "Changes were requested for {{entry}}.\n\n{{note}}\n\n<{{link}}>"],
            'workflow_approved' => [t('When a workflow is approved:'), t('“{{entry}}” is approved'), "{{entry}} completed its approval workflow and can now be published.\n\n<{{link}}>"],
            'workflow_invalidated' => [t('When a workflow review is invalidated:'), t('Approval reset for “{{entry}}”'), "The review was reset because {{entry}} changed.\n\n<{{link}}>"],
        ];
    }
}
