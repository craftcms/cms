<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Data;

use CraftCms\Cms\Release\Enums\ReleaseStatus;
use DateTimeInterface;

/**
 * @since 6.0.0
 */
readonly class ReleaseSummaryData
{
    public function __construct(
        public int $id,
        public string $name,
        public ReleaseStatus $status,
        public ?DateTimeInterface $targetDate,
        public ?DateTimeInterface $publishedAt,
        public ?int $itemCount,
    ) {}
}
