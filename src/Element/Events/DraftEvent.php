<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

abstract class DraftEvent
{
    public function __construct(
        public ElementInterface $canonical,
        public ?int $creatorId = null,
        public bool $provisional = false,
        public ?string $draftName = null,
        public ?string $draftNotes = null,
        public ?ElementInterface $draft = null,
    ) {}
}
