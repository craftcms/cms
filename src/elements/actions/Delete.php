<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;

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
        Craft::$app->getView()->registerJsWithVars(fn(
            $type,
            $elementType,
            $withDescendants,
            $hardDelete,
            $confirmationMessage,
        ) => <<<JS
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
    activate: async (selectedItems, elementIndex) => {
      await elementIndex.onBeforeDeleteElements(selectedItems);
      elementIndex.setIndexBusy();
      const elementIds = elementIndex.getSelectedElementIds();

      new Craft.ElementDeletionManager($elementType, elementIds, {
        siteId: elementIndex.siteId,
        ownerId: elementIndex.settings.criteria?.ownerId,
        withDescendants: $withDescendants,
        hardDelete: $hardDelete,
        confirmationMessage: $confirmationMessage,
        onLoadBlockers: () => {
          elementIndex.setIndexAvailable();
        },
        onSuccess: async () => {
          elementIndex.updateElements(true, true);
          await elementIndex.onDeleteElements(selectedItems);
        },
      });
    },
  });
})();
JS, [
            static::class,
            $this->elementType,
            $this->withDescendants,
            $this->hard,
            $this->getConfirmationMessage(),
        ]);

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
        return $this->confirmationMessage;
    }
}
