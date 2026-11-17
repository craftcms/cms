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
        public ?DateTimeInterface $targetDate,
        public bool $canManage,
        public int $draftId,
        public ?int $siteId,
        /**
         * Why the draft would stop the release from publishing, if it would.
         *
         * @var list<string>
         */
        public array $blockers,
    ) {}
}
