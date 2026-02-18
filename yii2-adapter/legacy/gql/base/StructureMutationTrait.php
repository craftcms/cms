<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Facades\Structures;
use GraphQL\Error\Error;

/**
 * Trait StructureMutationTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
trait StructureMutationTrait
{
    /**
     * @param ElementInterface $element
     * @param array $arguments
     */
    protected function performStructureOperations(ElementInterface $element, array $arguments): void
    {
        /** @var Element $element */
        $structureId = $element->structureId;

        if (empty($structureId)) {
            return;
        }

        if (!empty($arguments['prependTo'])) {
            Structures::prepend($structureId, $element, $this->getRelatedElement($arguments['prependTo']));
        } elseif (!empty($arguments['appendTo'])) {
            Structures::append($structureId, $element, $this->getRelatedElement($arguments['appendTo']));
        } elseif (!empty($arguments['prependToRoot'])) {
            Structures::prependToRoot($structureId, $element);
        } elseif (!empty($arguments['appendToRoot'])) {
            Structures::appendToRoot($structureId, $element);
        } elseif (!empty($arguments['insertBefore'])) {
            Structures::moveBefore($structureId, $element, $this->getRelatedElement($arguments['insertBefore']));
        } elseif (!empty($arguments['insertAfter'])) {
            Structures::moveAfter($structureId, $element, $this->getRelatedElement($arguments['insertAfter']));
        }
    }

    /**
     * Get the related element.
     *
     * @param int $elementId
     * @return ElementInterface
     */
    protected function getRelatedElement(int $elementId): ElementInterface
    {
        $relatedElement = Craft::$app->getElements()->getElementById($elementId, null, '*');

        if (!$relatedElement) {
            throw new Error('Unable to move element in a structure');
        }

        return $relatedElement;
    }
}
