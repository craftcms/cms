<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Events\WorkflowCommented;
use CraftCms\Cms\Workflow\Events\WorkflowTransitioned;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Uri;

use function CraftCms\Cms\t;

class SendUserReviewNotifications
{
    public function handleTransition(WorkflowTransitioned $event): void
    {
        $run = $event->run->loadMissing('activityRootEvent', 'author');
        $stage = $run->stages()->get($run->currentStage);

        match ($event->transition) {
            WorkflowTransition::Approve => $run->status === WorkflowStatus::Approved
                ? $this->sendApproved($event, $stage)
                : $this->sendReviewRequested($event, $stage),
            WorkflowTransition::Submit, WorkflowTransition::StageApproved => $this->sendReviewRequested($event, $stage),
            WorkflowTransition::Reject => $this->sendChangesRequested($event, $stage),
            WorkflowTransition::Invalidate => $this->sendInvalidated($event, $stage),
            default => null,
        };
    }

    public function handleComment(WorkflowCommented $event): void
    {
        $event->run->loadMissing('activityRootEvent', 'author');
        $this->sendCommented($event);
    }

    private function sendApproved(WorkflowTransitioned $event, ?WorkflowStageData $stage): void
    {
        $recipients = User::find()
            ->status(User::STATUS_ACTIVE)
            ->collect()
            ->filter(fn (User $user): bool => Gate::forUser($user)->allows('saveCanonical', $event->draft));
        $recipients->push($event->run->author->asElement());

        $this->send(
            $event,
            $stage,
            $recipients,
            'workflow_approved',
            t('Draft approved'),
            t('“{entry}” completed its approval workflow.', ['entry' => $event->draft->title]),
        );
    }

    private function sendChangesRequested(WorkflowTransitioned $event, ?WorkflowStageData $stage): void
    {
        $this->send(
            $event,
            $stage,
            collect([$event->run->author]),
            'workflow_changes_requested',
            t('Changes requested'),
            t('Changes were requested for “{entry}”.', ['entry' => $event->draft->title]),
        );
    }

    private function sendReviewRequested(WorkflowTransitioned $event, ?WorkflowStageData $stage): void
    {
        $context = $this->stageContext($event, $stage);
        $component = $stage?->component();

        if (! $event->run->isPending() || ! $component instanceof UserReviewStage || $context === null || $this->decisions($context) !== []) {
            return;
        }

        $this->send(
            $event,
            $stage,
            $component->reviewers($context),
            'workflow_review_requested',
            t('Review requested'),
            t('“{entry}” is awaiting your approval in {stage}.', [
                'entry' => $event->draft->title,
                'stage' => t($stage->name),
            ]),
        );
    }

    private function sendCommented(WorkflowCommented $event): void
    {
        $recipients = $event->actor->getCraftUserId() === $event->run->authorId
            ? $this->outstandingReviewers($event)
            : collect([$event->run->author]);

        $this->send(
            $event,
            $event->stage,
            $recipients,
            'workflow_review_commented',
            t('New review comment'),
            t('A comment was added to the review for “{entry}”.', ['entry' => $event->draft->title]),
        );
    }

    private function sendInvalidated(WorkflowTransitioned $event, ?WorkflowStageData $stage): void
    {
        $this->send(
            $event,
            $stage,
            collect([$event->run->author]),
            'workflow_invalidated',
            t('Approval reset'),
            t('The review for “{entry}” was reset because the draft changed.', ['entry' => $event->draft->title]),
        );
    }

    /** @return Collection<int, User> */
    private function outstandingReviewers(WorkflowCommented $event): Collection
    {
        $context = $this->stageContext($event, $event->stage);
        $component = $event->stage->component();

        if (! $component instanceof UserReviewStage || $context === null) {
            return collect();
        }

        $reviewerIds = collect($this->decisions($context))->pluck('reviewerId');

        return $component->reviewers($context)->whereNotIn('id', $reviewerIds);
    }

    private function stageContext(WorkflowTransitioned|WorkflowCommented $event, ?WorkflowStageData $stage): ?WorkflowStageContext
    {
        return $stage === null ? null : new WorkflowStageContext(
            $event->draft,
            $event->run,
            $stage,
            $event->run->payload[$stage->uid] ?? [],
        );
    }

    /** @return list<array<string, mixed>> */
    private function decisions(WorkflowStageContext $context): array
    {
        return is_array($context->payload['decisions'] ?? null) ? $context->payload['decisions'] : [];
    }

    /** @param Collection<int, mixed> $recipients */
    private function send(WorkflowTransitioned|WorkflowCommented $event, ?WorkflowStageData $stage, Collection $recipients, string $messageKey, string $title, string $message): void
    {
        $recipients = $recipients
            ->filter(fn ($user): bool => $user instanceof CraftUser && $user->getCraftUserId() !== $event->actor?->getCraftUserId())
            ->unique(fn (CraftUser $user): ?int => $user->getCraftUserId())
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new UserReviewNotification(
            messageKey: $messageKey,
            title: $title,
            message: $message,
            workflowName: (string) $event->run->activityRootEvent->data['workflow']['name'],
            entryTitle: (string) $event->draft->title,
            entryUrl: Uri::of($event->draft->getCpEditUrl())->withFragment('workflow')->value(),
            stageName: $stage === null ? null : t($stage->name),
            note: $event->note,
        ));
    }
}
