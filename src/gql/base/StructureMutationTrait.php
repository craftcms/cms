<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use GraphQL\Error\Error;

/**
 * Trait StructureMutationTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
trait StructureMutationTrait
{
    protected function performStructureOperations(ElementInterface $element, array $arguments)
    {
        /** @var Element $element */
        $structureId = $element->structureId;

        if (empty($structureId)) {
            return;
        }

        $structureService = Craft::$app->getStructures();

        if (!empty($arguments['prependTo'])) {
            $structureService->prepend($structureId, $element, $this->getRelatedElement($arguments['prependTo']));
        } else if (!empty($arguments['appendTo'])) {
            $structureService->append($structureId, $element, $this->getRelatedElement($arguments['appendTo']));
        } else if (!empty($arguments['prependToRoot'])) {
            $structureService->prependToRoot($structureId, $element);
        } else if (!empty($arguments['appendToRoot'])) {
            $structureService->appendToRoot($structureId, $element);
        } else if (!empty($arguments['insertBefore'])) {
            $structureService->moveBefore($structureId, $element, $this->getRelatedElement($arguments['insertBefore']));
        } else if (!empty($arguments['insertAfter'])) {
            $structureService->moveAfter($structureId, $element, $this->getRelatedElement($arguments['insertAfter']));
        }
    }

    /**
     * Get the related element.
     *
     * @param $elementId
     * @return ElementInterface
     */
    protected function getRelatedElement($elementId)
    {
        $relatedElement = Craft::$app->getElements()->getElementById($elementId);

        if (!$relatedElement) {
            throw new Error('Unable to move element in a structure');
        }

        return $relatedElement;
    }
}
