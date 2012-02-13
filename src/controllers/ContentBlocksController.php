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

		// Are we editing an existing block?
		$blockId = Blocks::app()->request->getPost('block_id');
		if ($blockId)
			$block = Blocks::app()->contentBlocks->getBlockById($blockId);

		// Otherwise create a new block
		if (empty($block))
			$block = new ContentBlock;

		$block->site_id = Blocks::app()->sites->currentSite->id;

		$block->name = Blocks::app()->request->getPost('name');
		$block->handle = Blocks::app()->request->getPost('handle');
		$block->class = Blocks::app()->request->getPost('class');
		$block->instructions = Blocks::app()->request->getPost('instructions');

		if ($block->save())
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Content block saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		$this->loadRequestedTemplate(array('block' => $block));
	}
}
