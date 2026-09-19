<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

readonly class UserReviewDecisionData
{
    public function __construct(
        public int $reviewerId,
        public UserReviewDecision $decision,
        public ?string $message,
        public string $decidedAt,
    ) {}

    /** @param array{reviewerId: int, decision: string, message: string|null, decidedAt: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['reviewerId'], UserReviewDecision::from($data['decision']), $data['message'], $data['decidedAt']);
    }

    /** @return array{reviewerId: int, decision: string, message: string|null, decidedAt: string} */
    public function toArray(): array
    {
        return ['reviewerId' => $this->reviewerId, 'decision' => $this->decision->value, 'message' => $this->message, 'decidedAt' => $this->decidedAt];
    }
}
