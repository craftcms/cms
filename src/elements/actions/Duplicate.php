<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

/**
 * Duplicate represents a Duplicate element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.30
 */
class Duplicate extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether to also duplicate the selected elements’ descendants
     */
    public $deep = false;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public $successMessage;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->deep
            ? Craft::t('app', 'Duplicate (with descendants)')
            : Craft::t('app', 'Duplicate');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        if ($this->deep) {
            $query->orderBy(['structureelements.lft' => SORT_ASC]);
        }

        /** @var Element[] $elements */
        $elements = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateElements($elements, $successCount, $failCount);

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
     * @param Element[] $elements
     * @param int[] $duplicatedElementIds
     * @param int $successCount
     * @param int $failCount
     * @param ElementInterface|null $newParent
     */
    private function _duplicateElements(array $elements, int &$successCount, int &$failCount, array &$duplicatedElementIds = [], ElementInterface $newParent = null)
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

            $newAttributes = [];
            if ($element::hasTitles()) {
                $newAttributes['title'] = Craft::t('app', '{title} copy', ['title' => $element->title]);
            }

            try {
                $duplicate = $elementsService->duplicateElement($element, $newAttributes);
            } catch (\Throwable $e) {
                // Validation error
                $failCount++;
                continue;
            }

            $successCount++;
            $duplicatedElementIds[$element->id] = true;

            if ($newParent) {
                // Append it to the duplicate of $element's parent
                $structuresService->append($element->structureId, $duplicate, $newParent);
            } else if ($element->structureId) {
                // Place it right next to the original element
                $structuresService->moveAfter($element->structureId, $duplicate, $element);
            }

            if ($this->deep) {
                $children = $element->getChildren()->anyStatus()->all();
                $this->_duplicateElements($children, $successCount, $failCount, $duplicatedElementIds, $duplicate);
            }
        }
    }
}
