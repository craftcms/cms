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
 * RenameFile represents a Rename File element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RenameFile extends ElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return Craft::t('app', 'Rename file');
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerHtml()
	{
		$type = JsonHelper::encode(static::className());
		$prompt = JsonHelper::encode(Craft::t('app', 'Enter the new filename'));

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: false,
		activate: function(\$selectedItems)
		{
			var \$element = \$selectedItems.find('.element'),
				fileId = \$element.data('id'),
				oldName = \$element.data('url').split('/').pop();

			if (oldName.indexOf('?') !== -1)
			{
				oldName = oldName.split('?').shift();
			}

			var newName = prompt({$prompt}, oldName);

			if (!newName || newName == oldName)
			{
				return;
			}

			Craft.elementIndex.setIndexBusy();

			var data = {
				fileId:   fileId,
				folderId: Craft.elementIndex.\$source.data('key').split(':')[1],
				filename: newName
			};

			var handleRename = function(response, textStatus)
			{
				Craft.elementIndex.setIndexAvailable();
				Craft.elementIndex.promptHandler.resetPrompts();

				if (textStatus == 'success')
				{
					if (response.prompt)
					{
						Craft.elementIndex.promptHandler.addPrompt(data);
						Craft.elementIndex.promptHandler.showBatchPrompts(function(choice)
						{
							choice = choice[0].choice;

							if (choice != 'cancel')
							{
								data.action = choice;
								Craft.postActionRequest('assets/move-file', data, handleRename);
							}
						});
					}

					if (response.success)
					{
						Craft.elementIndex.updateElements();

						// If assets were just merged we should get the reference tags updated right away
						Craft.cp.runPendingTasks();
					}

					if (response.error)
					{
						alert(response.error);
					}
				}
			};

			Craft.postActionRequest('assets/move-file', data, handleRename);
		}
	});
})();
EOT;

		Craft::$app->getView()->registerJs($js);
	}
}
