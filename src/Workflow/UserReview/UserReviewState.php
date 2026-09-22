<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

readonly class UserReviewState
{
    /**
     * @param  Collection<int, User>  $reviewers
     * @param  Collection<int, array<int, User>>  $reviewersByGroup
     * @param  Collection<int, int>  $localApprovalIds
     * @param  Collection<int, int>  $carriedApprovalIds
     * @param  Collection<int, int>  $effectiveApprovalIds
     */
    private function __construct(
        public Collection $reviewers,
        public Collection $reviewersByGroup,
        public Collection $localApprovalIds,
        public Collection $carriedApprovalIds,
        public Collection $effectiveApprovalIds,
    ) {}

    /** @param Collection<int, UserGroup> $groups */
    public static function for(WorkflowStageContext $context, Collection $groups): self
    {
        $reviewers = User::find()
            ->groupId($groups->pluck('id')->all())
            ->status(User::STATUS_ACTIVE)
            ->id(['not', $context->run->authorId])
            ->can('accessCp')
            ->collect()
            ->filter(fn (User $user): bool => Gate::forUser($user)->allows('view', $context->draft))
            ->values();
        $reviewerIds = $reviewers->pluck('id');
        $localApprovalIds = UserReviewDecisions::fromPayload($context->payload)->approvalIds();
        $carriedApprovalIds = ($context->payload['previousApprovalsReset'] ?? false)
            ? collect()
            : $context->previousStages
                ->filter(fn ($previous): bool => $previous->stage->component() instanceof UserReviewStage)
                ->flatMap(fn ($previous): Collection => UserReviewDecisions::fromPayload($previous->payload)->approvalIds())
                ->unique()
                ->values();

        return new self(
            $reviewers,
            $groups->mapWithKeys(fn (UserGroup $group): array => [$group->id => $reviewers->filter(
                fn (User $reviewer): bool => $group->users()->whereKey($reviewer->id)->exists(),
            )->values()->all()]),
            $localApprovalIds,
            $carriedApprovalIds->intersect($reviewerIds)->values(),
            $localApprovalIds->merge($carriedApprovalIds)->intersect($reviewerIds)->unique()->values(),
        );
    }

    /** @return Collection<int, User> */
    public function reviewersForGroup(UserGroup $group): Collection
    {
        return collect($this->reviewersByGroup->get($group->id, []));
    }
}
