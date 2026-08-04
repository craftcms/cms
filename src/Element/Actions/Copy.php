<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class Copy extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Copy');
    }

    public function getTriggerHtml(): ?string
    {
        // Only enable for copyable elements, per canCopy()
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-copyable')) {
          return false;
        }
      }

      return true;
    },
    activate: (selectedItems, elementIndex) => {
      let elements = $();
      selectedItems.each((i, item) => {
        elements = elements.add($(item).find('.element:first'));
      });
      Craft.cp.copyElements(elements);
    },
  })
})();
JS, [static::class]);

        return null;
    }
}
