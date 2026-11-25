<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;

/**
 * DeleteAssets represents a Delete element action, tuned for assets.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeleteAssets extends Delete
{
    /**
     * @inheritdoc
     * @since 3.5.15
     */
    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        Craft::$app->getView()->registerJsWithVars(fn(
            $triggerId,
            $elementType,
            $hardDelete,
            $confirmationMessage,
        ) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    triggerId: $triggerId,
    requireId: false,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        const element = selectedItems.eq(i).find('.element');
        if (Garnish.hasAttr(element, 'data-is-folder')) {
          if (selectedItems.length !== 1) {
            // only one folder at a time
            return false;
          }
          const sourcePath = element.data('source-path') || [];
          if (!sourcePath.length || !sourcePath[sourcePath.length - 1].canDelete) {
            return false;
          }
        } else {
          if (!Garnish.hasAttr(element, 'data-deletable')) {
            return false;
          }
        }
      }

      return elementIndex.settings.canDeleteElements(selectedItems);
    },
    activate: async (selectedItems, elementIndex) => {
      const element = selectedItems.find('.element:first');
      if (Garnish.hasAttr(element, 'data-is-folder')) {
        const sourcePath = element.data('source-path');
        await elementIndex.deleteFolder(sourcePath[sourcePath.length - 1]);
        elementIndex.updateElements();
      } else {
        await elementIndex.onBeforeDeleteElements(selectedItems);
        elementIndex.setIndexBusy();
        const elementIds = elementIndex.getSelectedElementIds();
    
        new Craft.ElementDeletionManager($elementType, elementIds, {
          siteId: elementIndex.siteId,
          ownerId: elementIndex.settings.criteria?.ownerId,
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
      }
    },
  });
})();
JS, [
            $this->getTriggerId(),
            $this->elementType,
            $this->hard,
            $this->confirmationMessage,
        ]);

        return null;
    }
}
