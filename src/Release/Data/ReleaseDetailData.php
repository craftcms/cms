<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Data;

use CraftCms\Cms\Release\Enums\ReleaseStatus;
use DateTimeInterface;

/**
 * @since 6.0.0
 */
readonly class ReleaseDetailData
{
    /** @param array{update: string} $urls */
    public function __construct(
        public int $id,
        public string $name,
        public ReleaseStatus $status,
        public ?DateTimeInterface $plannedAt,
        public ?DateTimeInterface $publishedAt,
        public ?string $lastError,
        public bool $canManage,
        public bool $canSchedule,
        public bool $canPublish,
        public bool $canUnschedule,
        public array $urls,
    ) {}
}
