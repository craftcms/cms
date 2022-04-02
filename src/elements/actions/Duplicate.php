<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use Throwable;

/**
 * Duplicate represents a Duplicate element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.30
 */
class Duplicate extends ElementAction
{
    /**
     * @var bool Whether to also duplicate the selected elements’ descendants
     */
    public bool $deep = false;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->deep
            ? Craft::t('app', 'Duplicate (with descendants)')
            : Craft::t('app', 'Duplicate');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        if ($this->deep) {
            $query->orderBy(['structureelements.lft' => SORT_ASC]);
        }

        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($query, $elements, $successCount, $failCount);

        // Did all of them fail?
        if ($successCount === 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate elements due to validation errors.'));
            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Could not duplicate all elements due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('app', 'Elements duplicated.'));
        }

        return true;
    }

    /**
     * @param ElementQueryInterface $query
     * @param ElementInterface[] $elements
     * @param int[] $duplicatedElementIds
     * @param int $successCount
     * @param int $failCount
     * @param ElementInterface|null $newParent
     */
    private function _duplicateElements(ElementQueryInterface $query, array $elements, int &$successCount, int &$failCount, array &$duplicatedElementIds = [], ?ElementInterface $newParent = null): void
    {
        $elementsService = Craft::$app->getElements();
        $structuresService = Craft::$app->getStructures();

        foreach ($elements as $element) {
            // Make sure this element wasn't already duplicated, which could
            // happen if it's the descendant of a previously duplicated element
            // and $this->deep == true.
            if (isset($duplicatedElementIds[$element->id])) {
                continue;
            }

            try {
                $duplicate = $elementsService->duplicateElement($element);
            } catch (Throwable) {
                // Validation error
                $failCount++;
                continue;
            }

            $successCount++;
            $duplicatedElementIds[$element->id] = true;

            if ($newParent) {
                // Append it to the duplicate of $element’s parent
                $structuresService->append($element->structureId, $duplicate, $newParent);
            } elseif ($element->structureId) {
                // Place it right next to the original element
                $structuresService->moveAfter($element->structureId, $duplicate, $element);
            }

            if ($this->deep) {
                // Don't use $element->children() here in case its lft/rgt values have changed
                $children = $element::find()
                    ->siteId($element->siteId)
                    ->descendantOf($element->id)
                    ->descendantDist(1)
                    ->status(null)
                    ->all();

                $this->_duplicateElements($query, $children, $successCount, $failCount, $duplicatedElementIds, $duplicate);
            }
        }
    }
}
