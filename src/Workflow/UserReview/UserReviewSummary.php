<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Collection;

/**
 * @since 6.0.0
 */
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
            return [...$props, 'approvals' => $state->effectiveApprovalIds->count(), ...self::reviewers($state, $state->reviewers)];
        }

        return [...$props, 'groups' => $groups->map(function (UserGroup $group) use ($state): array {
            $reviewers = $state->reviewersForGroup($group);

            return ['name' => $group->name, 'approvals' => $state->effectiveApprovalIds->intersect($reviewers->pluck('id'))->count(), ...self::reviewers($state, $reviewers)];
        })->values()->all()];
    }

    /**
     * @param  Collection<int, User>  $reviewers
     * @return array<string, list<array{name: string}>>
     */
    private static function reviewers(UserReviewState $state, Collection $reviewers): array
    {
        return [
            'approvedReviewers' => self::names($reviewers->whereIn('id', $state->localApprovalIds)),
            'carriedReviewers' => self::names($reviewers->whereIn('id', $state->carriedApprovalIds)),
            'requiredReviewers' => self::names($reviewers->whereIn('id', $state->requiredApprovalIds)),
            'remainingReviewers' => self::names($reviewers->whereNotIn('id', $state->effectiveApprovalIds->merge($state->requiredApprovalIds))),
        ];
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
