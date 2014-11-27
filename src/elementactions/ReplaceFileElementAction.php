<?php
namespace Craft;

/**
 * Replace File Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class ReplaceFileElementAction extends BaseElementAction
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
		return Craft::t('Replace file');
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
		handle: 'ReplaceFile',
		batch: false,
		activate: function(\$selectedItems)
		{
			$('.replaceFile').remove();

			var \$element = \$selectedItems.find('.element'),
				\$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod),
				options = Craft.elementIndex._currentUploaderSettings;

			options.url = Craft.getActionUrl('assets/replaceFile');
			options.dropZone = null;
			options.fileInput = \$fileInput;

			var tempUploader = new Craft.Uploader(\$fileInput, options);
			tempUploader.setParams({
				fileId: \$element.data('id')
			});

			\$fileInput.click();
		}
	});
})();
EOT;

		craft()->templates->includeJs($js);
	}
}
