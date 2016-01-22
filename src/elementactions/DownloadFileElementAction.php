<?php
namespace Craft;

/**
 * Download File Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @link      http://craftcms.com
 * @package   craft.app.elementactions
 * @since     2.6
 */
class DownloadFileElementAction extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Download file');
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'DownloadFile',
		batch: false,
		activate: function(\$selectedItems)
		{
			$('<form method="post" target="_blank" action="">' +
			'<input type="hidden" name="action" value="assets/downloadAsset" />' +
			'<input type="hidden" name="assetId" value="' + \$selectedItems.data('id') + '" />' +
			'</form>').submit();
		}
	});
})();
EOT;

		craft()->templates->includeJs($js);
	}
}
