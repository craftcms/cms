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
use craft\base\NestedElementInterface;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\services\Elements;

/**
 * Delete represents a Delete element action.
 *
 * Element types that make this action available should implement [[ElementInterface::canDelete()]] to explicitly state whether they can be
 * deleted by the current user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Delete extends ElementAction implements DeleteActionInterface
{
    /**
     * @var bool Whether to delete the element’s descendants as well.
     * @since 3.5.0
     */
    public bool $withDescendants = false;

    /**
     * @var bool Whether to permanently delete the elements.
     * @since 3.5.0
     */
    public bool $hard = false;

    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public ?string $confirmationMessage = null;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;

    /**
     * @inheritdoc
     */
    public function canHardDelete(): bool
    {
        return !$this->withDescendants;
    }

    /**
     * @inheritdoc
     */
    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-deletable')) {
          return false;
        }
      }

      return elementIndex.settings.canDeleteElements(selectedItems);
    },
    beforeActivate: async (selectedItems, elementIndex) => {
      await elementIndex.settings.onBeforeDeleteElements(selectedItems);
    },
    afterActivate: async (selectedItems, elementIndex) => {
      await elementIndex.settings.onDeleteElements(selectedItems);
    },
  });
})();
JS, [static::class]);

        if ($this->hard) {
            return Html::tag('div', $this->getTriggerLabel(), [
                'class' => ['btn', 'formsubmit'],
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return Craft::t('app', 'Delete permanently');
        }

        if ($this->withDescendants) {
            return Craft::t('app', 'Delete (with descendants)');
        }

        return Craft::t('app', 'Delete');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage(): ?string
    {
        if (isset($this->confirmationMessage)) {
            return $this->confirmationMessage;
        }

        if ($this->hard) {
            return Craft::t('app', 'Are you sure you want to permanently delete the selected {type}?', [
                'type' => $this->elementType::pluralLowerDisplayName(),
            ]);
        }

        if ($this->withDescendants) {
            return Craft::t('app', 'Are you sure you want to delete the selected {type} along with their descendants?', [
                'type' => $this->elementType::pluralLowerDisplayName(),
            ]);
        }

        return Craft::t('app', 'Are you sure you want to delete the selected {type}?', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $withDescendants = $this->withDescendants && !$this->hard;
        $elementsService = Craft::$app->getElements();

        if ($withDescendants) {
            $query
                ->with([
                    [
                        'descendants',
                        [
                            'orderBy' => ['structureelements.lft' => SORT_DESC],
                            'status' => null,
                        ],
                    ],
                ])
                ->orderBy(['structureelements.lft' => SORT_DESC]);
        }

        $deletedElementIds = [];
        $user = Craft::$app->getUser()->getIdentity();

        $deleteOwnership = [];

        foreach ($query->all() as $element) {
            if (!$elementsService->canView($element, $user) || !$elementsService->canDelete($element, $user)) {
                continue;
            }
            if (!isset($deletedElementIds[$element->id])) {
                if ($withDescendants) {
                    foreach ($element->getDescendants()->all() as $descendant) {
                        if (
                            !isset($deletedElementIds[$descendant->id]) &&
                            $elementsService->canView($descendant, $user) &&
                            $elementsService->canDelete($descendant, $user)
                        ) {
                            $this->deleteElement($descendant, $elementsService, $deleteOwnership);
                            $deletedElementIds[$descendant->id] = true;
                        }
                    }
                }
                $this->deleteElement($element, $elementsService, $deleteOwnership);
                $deletedElementIds[$element->id] = true;
            }
        }

        foreach ($deleteOwnership as $ownerId => $elementIds) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $elementIds,
                'ownerId' => $ownerId,
            ]);
        }

        if (isset($this->successMessage)) {
            $this->setMessage($this->successMessage);
        } else {
            $this->setMessage(Craft::t('app', '{type} deleted.', [
                'type' => $this->elementType::pluralDisplayName(),
            ]));
        }

        return true;
    }

    private function deleteElement(
        ElementInterface $element,
        Elements $elementsService,
        array &$deleteOwnership,
    ): void {
        // If the element primarily belongs to a different element, (and we're not hard deleting) just delete the ownership
        if (!$this->hard && $element instanceof NestedElementInterface) {
            $ownerId = $element->getOwnerId();
            if ($ownerId && $element->getPrimaryOwnerId() !== $ownerId) {
                $deleteOwnership[$ownerId][] = $element->id;
                return;
            }
        }

        $elementsService->deleteElement($element, $this->hard);
    }
}
