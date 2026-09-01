<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Structures;
use GraphQL\Error\Error;

trait PerformsStructureMutations
{
    /**
     * @param  array{prependTo?: int, appendTo?: int, prependToRoot?: bool, appendToRoot?: bool, insertBefore?: int, insertAfter?: int}  $arguments
     */
    protected function performStructureOperations(ElementInterface $element, array $arguments): void
    {
        /** @var Element $element */
        $structureId = $element->structureId;

        if (empty($structureId)) {
            return;
        }

        match (true) {
            ! empty($arguments['prependTo']) => Structures::prepend($structureId, $element, $this->getRelatedElement($arguments['prependTo'])),
            ! empty($arguments['appendTo']) => Structures::append($structureId, $element, $this->getRelatedElement($arguments['appendTo'])),
            ! empty($arguments['prependToRoot']) => Structures::prependToRoot($structureId, $element),
            ! empty($arguments['appendToRoot']) => Structures::appendToRoot($structureId, $element),
            ! empty($arguments['insertBefore']) => Structures::moveBefore($structureId, $element, $this->getRelatedElement($arguments['insertBefore'])),
            ! empty($arguments['insertAfter']) => Structures::moveAfter($structureId, $element, $this->getRelatedElement($arguments['insertAfter'])),
            default => null,
        };
    }

    protected function getRelatedElement(int $elementId): ElementInterface
    {
        $relatedElement = Elements::getElementById($elementId, null, '*');

        if (! $relatedElement) {
            throw new Error('Unable to move element in a structure');
        }

        return $relatedElement;
    }
}
