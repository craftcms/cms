<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Collection;

readonly class UserReviewSummary
{
    /**
     * @param  Collection<int, UserGroup>  $groups
     * @return array<string, mixed>
     */
    public static function props(UserReviewStage $stage, UserReviewState $state, Collection $groups): array
    {
        $props = ['approvalMode' => $stage->approvalMode->value, 'approvalsRequired' => $stage->approvalsRequired];
        if ($stage->approvalMode === UserReviewApprovalMode::Total) {
            return [...$props, 'approvals' => $state->effectiveApprovalIds->count(), 'approvedReviewers' => self::names($state->reviewers->whereIn('id', $state->localApprovalIds)), 'carriedReviewers' => self::names($state->reviewers->whereIn('id', $state->carriedApprovalIds)), 'remainingReviewers' => self::names($state->reviewers->whereNotIn('id', $state->effectiveApprovalIds))];
        }

        return [...$props, 'groups' => $groups->map(function (UserGroup $group) use ($state): array {
            $reviewers = $state->reviewersForGroup($group);

            return ['name' => $group->name, 'approvals' => $state->effectiveApprovalIds->intersect($reviewers->pluck('id'))->count(), 'approvedReviewers' => self::names($reviewers->whereIn('id', $state->localApprovalIds)), 'carriedReviewers' => self::names($reviewers->whereIn('id', $state->carriedApprovalIds)), 'remainingReviewers' => self::names($reviewers->whereNotIn('id', $state->effectiveApprovalIds))];
        })->values()->all()];
    }

    /**
     * @param  Collection<int, User>  $reviewers
     * @return list<array{name: string}>
     */
    private static function names(Collection $reviewers): array
    {
        return $reviewers->map(fn (User $reviewer): array => ['name' => $reviewer->name])->values()->all();
    }
}
