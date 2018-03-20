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
 * DownloadAssetFile represents a Download Asset element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DownloadAssetFile extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Download file');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        activate: function(\$selectedItems)
        {
            var form = $('<form method="post" target="_blank" action="">' +
            '<input type="hidden" name="action" value="assets/download-asset" />' +
            '<input type="hidden" name="assetId" value="' + \$selectedItems.data('id') + '" />' +
            '<input type="hidden" name="{csrfName}" value="{csrfValue}" />' +
            '<input type="submit" value="Submit" />' +
            '</form>');
            
            form.appendTo('body');
            form.submit();
            form.remove();
        }
    });
})();
EOD;

        $js = str_replace([
            '{csrfName}',
            '{csrfValue}'
        ], [
            Craft::$app->getConfig()->getGeneral()->csrfTokenName,
            Craft::$app->getRequest()->getCsrfToken()
        ], $js);

        Craft::$app->getView()->registerJs($js);
    }
}
