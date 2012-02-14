<?php
namespace Blocks;

/**
 * Handles content block management tasks
 */
class ContentBlocksController extends BaseController
{
	/**
	 * Saves a content block
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		$blockSettings['name'] = Blocks::app()->request->getPost('name');
		$blockSettings['handle'] = Blocks::app()->request->getPost('handle');
		$blockSettings['class'] = Blocks::app()->request->getPost('class');
		$blockSettings['instructions'] = Blocks::app()->request->getPost('instructions');

		$blockTypeSettings = Blocks::app()->request->getPost($blockSettings['class']);
		$blockId = Blocks::app()->request->getPost('block_id');

		$block = Blocks::app()->contentBlocks->saveBlock($blockSettings, $blockTypeSettings, $blockId);

		// Did it save?
		if (!$block->isNewRecord)
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Content block saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		// Save the block type settings on the block type
		$block->blockType->settings = $blockTypeSettings;

		// Reload the original template
		$this->loadRequestedTemplate(array('block' => $block));
	}
}
