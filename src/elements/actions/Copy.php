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
 * Copy represents a Copy element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
class Copy extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Copy');
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getTriggerHtml(): ?string
    {
        // Only enable for copyable elements, per canCopy()
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
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
  });
})();
JS, [static::class]);

        return null;
    }
}
