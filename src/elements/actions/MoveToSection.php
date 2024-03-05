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
use craft\elements\Entry;
use yii\base\Exception;

/**
 * MoveToSection represents a Move to Section element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class MoveToSection extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Move to section');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;

        if ($elementType !== Entry::class) {
            throw new Exception("Move to section is only available for Entries.");
        }

        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        // validateSelection: (selectedItems, elementIndex) => {
        //   return elementIndex.settings.canMoveElements(selectedItems);
        // },
        validateSelection: (selectedItems, elementIndex) => {
          let valid = true;
          for (let i = 0; i < selectedItems.length; i++) {
            const \$element = selectedItems.eq(i).find('.element');
            if (\$element.data('status') === 'draft' ||
                \$element.data('status') === 'trashed') {
              valid = false;
            }
          }
          
          return valid;
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
