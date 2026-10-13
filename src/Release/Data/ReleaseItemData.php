<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Data;

use DateTimeInterface;

/**
 * @since 6.0.0
 */
readonly class ReleaseItemData
{
    public function __construct(
        public int $id,
        public bool $restricted,
        public string $title,
        public ?int $draftId,
        public ?int $canonicalId,
        public ?string $draftName,
        public ?string $url,
        public ?DateTimeInterface $publishedAt,
        public bool $canDetach,
    ) {}
}
