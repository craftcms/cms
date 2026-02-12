<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use yii\base\Exception;

/**
 * CopyReferenceTag represents a Copy Reference Tag element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CopyReferenceTag extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Copy reference tag');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        $refHandle = $this->elementType::refHandle();
        if ($refHandle === null) {
            throw new Exception("Element type \"$this->elementType\" doesn't have a reference handle.");
        }

        Craft::$app->getView()->registerJsWithVars(fn($type, $refHandle) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: (selectedItems, elementIndex) => {
            Craft.ui.createCopyTextPrompt({
                label: Craft.t('app', 'Copy the reference tag'),
                value: '{' + $refHandle + ':' + selectedItems.find('.element').data('id') + '}',
            });
        },
    });
})();
JS, [static::class, $refHandle]);

        return null;
    }
}
