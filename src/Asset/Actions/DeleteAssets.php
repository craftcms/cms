<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Support\Facades\HtmlStack;

class DeleteAssets extends Delete
{
    #[\Override]
    public function getTriggerHtml(): ?string
    {
        // Only enable for deletable elements, per canDelete()
        HtmlStack::jsWithVars(fn (
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
