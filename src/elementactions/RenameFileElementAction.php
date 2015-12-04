<?php
namespace Craft;

/**
 * Rename File Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @link      http://craftcms.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class RenameFileElementAction extends BaseElementAction
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
		return Craft::t('Rename file');
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$prompt = JsonHelper::encode(Craft::t('Enter the new filename'));

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'RenameFile',
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

			var newName = prompt($prompt, oldName);

			if (!newName || newName == oldName)
			{
				return;
			}

			Craft.elementIndex.setIndexBusy();

			var data = {
				fileId:   fileId,
				folderId: Craft.elementIndex.\$source.data('key').split(':')[1],
				fileName: newName
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
								Craft.postActionRequest('assets/moveFile', data, handleRename);
							}
						});
					}

					if (response.success)
					{
						Craft.elementIndex.updateElements();

						// If assets were just merged we should get the referece tags updated right away
						Craft.cp.runPendingTasks();
					}

					if (response.error)
					{
						alert(response.error);
					}
				}
			};

			Craft.postActionRequest('assets/moveFile', data, handleRename);
		}
	});
})();
EOT;

		craft()->templates->includeJs($js);
	}
}
