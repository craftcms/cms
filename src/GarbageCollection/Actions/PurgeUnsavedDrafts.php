<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\GarbageCollection\GarbageCollection;

class PurgeUnsavedDrafts extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        private readonly Drafts $drafts,
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        if ($this->generalConfig->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $this->components->task(
            'purging unsaved drafts that have gone stale',
            function () {
                $this->drafts->purgeUnsavedDrafts();
            },
        );
    }
}
