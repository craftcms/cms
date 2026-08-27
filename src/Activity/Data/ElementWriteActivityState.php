<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;

/** @internal */
class ElementWriteActivityState
{
    public bool $draftContentChanged = false;

    public function __construct(
        public readonly bool $recordActivity,
        public readonly bool $recordEntry,
        public readonly ?Entry $originalEntry,
        public readonly ?Asset $originalAsset,
        public readonly bool $recordDraft,
        public readonly bool $isNewDraft,
        public readonly bool $draftMetadataChanged,
    ) {}
}
