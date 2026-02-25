<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use craft\base\ElementAction;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use yii\base\Exception;
use function CraftCms\Cms\t;

/**
 * MoveToSection represents a Move to Section element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class MoveToSection extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return t('Move to…');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        if ($this->elementType !== Entry::class) {
            throw new Exception("Move to section is only available for Entries.");
        }

        HtmlStack::jsWithVars(fn($type) => <<<JS
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
