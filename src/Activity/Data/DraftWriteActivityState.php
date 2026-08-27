<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

/** @internal */
class DraftWriteActivityState
{
    public bool $contentChanged = false;

    public function __construct(
        public readonly bool $isNew,
        public readonly bool $metadataChanged,
    ) {}
}
