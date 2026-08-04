<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use RuntimeException;

use function CraftCms\Cms\t;

class MoveToSection extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Move to…');
    }

    public function getTriggerHtml(): ?string
    {
        if ($this->elementType !== Entry::class) {
            throw new RuntimeException('Move to section is only available for Entries.');
        }

        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: (selectedItems, elementIndex) => {
          for (let i = 0; i < selectedItems.length; i++) {
            if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-movable')) {
              return false;
            }
          }

          return true;
        },
        activate: (selectedItems, elementIndex) => {
          let entryIds = [];
          for (let i = 0; i < selectedItems.length; i++) {
            entryIds.push(selectedItems.eq(i).find('.element').data('id'));
          }

          new Craft.EntryMover(entryIds, elementIndex);
        },
    })
})();
JS, [static::class]);

        return null;
    }
}
