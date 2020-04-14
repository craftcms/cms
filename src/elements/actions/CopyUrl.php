<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

/**
 * CopyUrl represents a Copy URL element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class CopyUrl extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Copy URL');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<JS
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return !!\$selectedItems.find('.element').data('url');
        },
        activate: function(\$selectedItems)
        {
            Craft.ui.createCopyTextPrompt({
                label: Craft.t('app', 'Copy the URL'),
                value: \$selectedItems.find('.element').data('url'),
            });
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
    }
}
