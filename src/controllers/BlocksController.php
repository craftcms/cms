<?php
namespace Blocks;

/**
 * Handles content block management tasks
 */
class BlocksController extends BaseController
{
	/**
	 * All content block actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

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

		$block = Blocks::app()->blocks->saveBlock($blockSettings, $blockTypeSettings, $blockId);

		// Did it save?
		if (!$block->errors)
		{
			if (Blocks::app()->request->isAjaxRequest)
			{
				$r = array(
					'success' => true,
					'id'      => $block->id,
					'name'    => $block->name,
					'type'    => $block->blockType->name
				);
				$this->returnJson($r);
			}

			Blocks::app()->user->setMessage(MessageStatus::Success, 'Content block saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		if (Blocks::app()->request->isAjaxRequest)
		{
			$r = array('errors' => $block->errors);
			$this->returnJson($r);
		}

		// Reload the original template
		$this->loadRequestedTemplate(array('block' => $block));
	}
}
