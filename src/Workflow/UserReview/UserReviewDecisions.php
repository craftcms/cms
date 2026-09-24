<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use Illuminate\Support\Collection;

readonly class UserReviewDecisions
{
    /** @param list<UserReviewDecisionData> $all */
    private function __construct(public array $all) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        return new self(array_map(UserReviewDecisionData::fromArray(...), $payload['decisions'] ?? []));
    }

    public function hasRejection(): bool
    {
        return collect($this->all)->contains(fn (UserReviewDecisionData $decision): bool => $decision->decision === UserReviewDecision::Rejected);
    }

    public function hasDecisionFrom(int $reviewerId): bool
    {
        return collect($this->all)->contains(fn (UserReviewDecisionData $decision): bool => $decision->reviewerId === $reviewerId);
    }

    /** @return Collection<int, int> */
    public function approvalIds(): Collection
    {
        return collect($this->all)->filter(fn (UserReviewDecisionData $decision): bool => $decision->decision === UserReviewDecision::Approved)->pluck('reviewerId');
    }

    /** @return list<array{reviewerId: int, decision: string, message: string|null, decidedAt: string}> */
    public function append(int $reviewerId, UserReviewDecision $decision, ?string $message): array
    {
        return [...collect($this->all)->map(fn (UserReviewDecisionData $decision): array => $decision->toArray())->all(), new UserReviewDecisionData($reviewerId, $decision, $message, now()->toIso8601String())->toArray()];
    }
}
