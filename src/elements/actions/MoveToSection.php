<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Entry;
use yii\base\Exception;

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
        return Craft::t('app', 'Move to…');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        if ($this->elementType !== Entry::class) {
            throw new Exception("Move to section is only available for Entries.");
        }

        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
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
    });
})();
JS, [static::class]);

        return null;
    }
}
