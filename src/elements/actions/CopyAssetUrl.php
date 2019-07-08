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
 * CopyAssetUrl represents a Download Asset element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class CopyAssetUrl extends ElementAction
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
        $prompt = Json::encode(Craft::t('app', '{ctrl}C to copy.'));

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return \$selectedItems.find('.element').data('url') != '';
        },
        activate: function(\$selectedItems)
        {
            var message = Craft.t('app', {$prompt}, {
                ctrl: (navigator.appVersion.indexOf('Mac') !== -1 ? '⌘' : 'Ctrl-')
            });

            prompt(message, \$selectedItems.find('.element').data('url'));
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
