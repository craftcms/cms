<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Data;

use CraftCms\Cms\Release\Enums\ReleaseStatus;
use DateTimeInterface;

/**
 * @since 6.0.0
 */
readonly class AttachedReleaseData
{
    public function __construct(
        public int $id,
        public string $name,
        public ReleaseStatus $status,
        public ?DateTimeInterface $plannedAt,
        public bool $canManage,
        public bool $canDetach,
        public bool $canPublishAhead,
        public int $draftId,
        public ?int $siteId,
    ) {}
}
