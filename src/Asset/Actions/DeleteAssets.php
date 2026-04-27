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
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
  const trigger = new Craft.ElementActionTrigger({
    type: $type,
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

      return true;
    },

    activate: (selectedItems, elementIndex) => {
      const element = selectedItems.find('.element:first');
      if (Garnish.hasAttr(element, 'data-is-folder')) {
        const sourcePath = element.data('source-path');
        elementIndex.deleteFolder(sourcePath[sourcePath.length - 1])
          .then(() => {
            elementIndex.updateElements();
          });
      } else {
        elementIndex.submitAction(trigger.\$trigger.data('action'), Garnish.getPostData(trigger.\$trigger));
      }
    },
  });
})();
JS, [static::class]);

        return null;
    }
}
