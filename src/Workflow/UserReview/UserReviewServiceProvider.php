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

/**
 * @since 6.0.0
 */
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
                'variables' => [
                    'workflow' => t('Workflow name'),
                    'entry' => t('Entry title'),
                    'stage' => t('Stage name'),
                    'note' => t('Review note'),
                    'user' => t('Name of the user who acted'),
                    'link' => t('Entry link'),
                ],
            ]));
        }
    }

    /** @return array<string, array{string, string, string}> */
    private function messageDefinitions(): array
    {
        return [
            'workflow_review_requested' => [t('When a workflow review is requested:'), t('Review requested for “{{entry}}”'), "{{entry}} is awaiting your approval in {{stage}}.\n\n<{{link}}>"],
            'workflow_review_commented' => [t('When a workflow review is commented on:'), t('New review comment on “{{entry}}”'), "{{user}} commented on {{entry}} during {{stage}}.\n\n{{note}}\n\n<{{link}}>"],
            'workflow_changes_requested' => [t('When changes are requested:'), t('Changes requested for “{{entry}}”'), "{{user}} requested changes to {{entry}}.\n\n{{note}}\n\n<{{link}}>"],
            'workflow_approved' => [t('When a workflow is approved:'), t('“{{entry}}” is approved'), "{{entry}} completed its approval workflow.\n\n<{{link}}>"],
            'workflow_invalidated' => [t('When a workflow review is reset because its workflow changed:'), t('Approval reset for “{{entry}}”'), "The review for {{entry}} was reset because its workflow changed.\n\n<{{link}}>"],
        ];
    }
}
