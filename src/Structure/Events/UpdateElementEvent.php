<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Structure\Enums\Action;
use RuntimeException;

abstract class UpdateElementEvent
{
    private ?ElementInterface $targetElement = null;

    public function __construct(
        public ElementInterface $element,

        /**
         * @var int The ID of the structure the element is being moved within.
         */
        public int $structureId,

        /**
         * @var int|null The ID of the element that [[element]] is being moved in reference to, or `null` if
         *               [[element]] is being appended/prepended to the root of the structure.
         */
        public ?int $targetElementId,

        /**
         * @var Action The type of structure action being performed.
         */
        public Action $action,
    ) {}

    public function getTargetElement(): ?ElementInterface
    {
        if (! isset($this->targetElementId)) {
            return null;
        }

        if (isset($this->targetElement)) {
            return $this->targetElement;
        }

        $targetElement = $this->element::find()
            ->id($this->targetElementId)
            ->site('*')
            ->preferSites([$this->element->siteId])
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->structureId($this->structureId)
            ->one();

        throw_unless($targetElement, new RuntimeException("Invalid target element ID: $this->targetElementId"));

        return $this->targetElement = $targetElement;
    }
}
