<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\helpers\JsonHelper;

/**
 * ReplaceFile represents a Replace File element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ReplaceFile extends ElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return Craft::t('app', 'Replace file');
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerHtml()
	{
		$type = JsonHelper::encode(static::className());

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		activate: function(\$selectedItems)
		{
			$('.replaceFile').remove();

			var \$element = \$selectedItems.find('.element'),
				\$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod),
				options = Craft.elementIndex._currentUploaderSettings;

			options.url = Craft.getActionUrl('assets/replace-file');
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

		Craft::$app->getView()->registerJs($js);
	}
}
