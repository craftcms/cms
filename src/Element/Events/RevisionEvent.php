<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

abstract class RevisionEvent
{
    public function __construct(
        public ElementInterface $canonical,
        public int $revisionNum,
        public ?int $creatorId = null,
        public ?string $revisionNotes = null,
        public ?ElementInterface $revision = null,
    ) {}
}
