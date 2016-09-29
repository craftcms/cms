<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\helpers\Json;

/**
 * DownloadAssetFile represents a Download Asset element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DownloadAssetFile extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return Craft::t('app', 'Download Asset file');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOT
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
EOT;

        $js = str_replace("{csrfName}", Craft::$app->getConfig()->get('csrfTokenName'), $js);
        $js = str_replace("{csrfValue}", Craft::$app->getRequest()->getCsrfToken(), $js);

        Craft::$app->getView()->registerJs($js);
    }
}
