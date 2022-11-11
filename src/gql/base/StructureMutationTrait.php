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
        $siteId = $arguments['siteId'] ?? null;

        if (!empty($arguments['prependTo'])) {
            $structureService->prepend($structureId, $element, $this->getRelatedElement($arguments['prependTo'], $siteId));
        } elseif (!empty($arguments['appendTo'])) {
            $structureService->append($structureId, $element, $this->getRelatedElement($arguments['appendTo'], $siteId));
        } elseif (!empty($arguments['prependToRoot'])) {
            $structureService->prependToRoot($structureId, $element);
        } elseif (!empty($arguments['appendToRoot'])) {
            $structureService->appendToRoot($structureId, $element);
        } elseif (!empty($arguments['insertBefore'])) {
            $structureService->moveBefore($structureId, $element, $this->getRelatedElement($arguments['insertBefore'], $siteId));
        } elseif (!empty($arguments['insertAfter'])) {
            $structureService->moveAfter($structureId, $element, $this->getRelatedElement($arguments['insertAfter'], $siteId));
        }
    }

    /**
     * Get the related element.
     *
     * @param $elementId
     * @param int|null $siteId
     * @return ElementInterface
     */
    protected function getRelatedElement($elementId, int $siteId = null): ElementInterface
    {
        $relatedElement = Craft::$app->getElements()->getElementById($elementId, null, $siteId);

        if (!$relatedElement) {
            throw new Error('Unable to move element in a structure');
        }

        return $relatedElement;
    }
}
