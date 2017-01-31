<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tasks;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Task;

/**
 * UpdateElementSlugsAndUris represents an Update Element Slugs and URIs background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateElementSlugsAndUris extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public $elementId;

    /**
     * @var string|ElementInterface|null The type of elements to update.
     */
    public $elementType;

    /**
     * @var int|null The site ID of the elements to update.
     */
    public $siteId;

    /**
     * @var bool Whether the elements’ other sites should be updated as well.
     */
    public $updateOtherSites = true;

    /**
     * @var bool Whether the elements’ descendants should be updated as well.
     */
    public $updateDescendants = true;

    /**
     * @var
     */
    private $_elementIds;

    /**
     * @var
     */
    private $_skipRemainingEntries;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
    {
        $this->_elementIds = (array)$this->elementId;
        $this->_skipRemainingEntries = false;

        return count($this->_elementIds);
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        if ($this->_skipRemainingEntries) {
            return true;
        }

        $elementsService = Craft::$app->getElements();
        /** @var Element $element */
        $element = $elementsService->getElementById($this->_elementIds[$step], $this->elementType, $this->siteId);

        // Make sure they haven't deleted this element
        if (!$element) {
            return true;
        }

        $oldSlug = $element->slug;
        $oldUri = $element->uri;

        $elementsService->updateElementSlugAndUri($element, $this->updateOtherSites, false, false);

        // Only go deeper if something just changed
        if ($this->updateDescendants && ($element->slug !== $oldSlug || $element->uri !== $oldUri)) {
            /** @var Element $elementType */
            $elementType = $this->elementType;

            $childIds = $elementType::find()
                ->descendantOf($element)
                ->descendantDist(1)
                ->status(null)
                ->enabledForSite(false)
                ->siteId($element->siteId)
                ->ids();

            if (!empty($childIds)) {
                $this->runSubTask([
                    'type' => static::class,
                    'description' => Craft::t('app', 'Updating children'),
                    'elementId' => $childIds,
                    'elementType' => $this->elementType,
                    'siteId' => $this->siteId,
                    'updateOtherSites' => $this->updateOtherSites,
                    'updateDescendants' => true,
                ]);
            }
        } else if ($step === 0) {
            // Don't bother updating the other entries
            $this->_skipRemainingEntries = true;
        }

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating element slugs and URIs');
    }
}
