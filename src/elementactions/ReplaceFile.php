<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\Craft;

/**
 * Replace File Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ReplaceFile extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Replace file');
	}

	/**
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
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

		Craft::$app->templates->includeJs($js);
	}
}
