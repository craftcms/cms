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

		$blockSettings['name'] = b()->request->getPost('name');
		$blockSettings['handle'] = b()->request->getPost('handle');
		$blockSettings['class'] = b()->request->getPost('class');
		$blockSettings['instructions'] = b()->request->getPost('instructions');

		$blockTypeSettings = b()->request->getPost($blockSettings['class']);
		$blockId = b()->request->getPost('block_id');

		$block = b()->blocks->saveBlock($blockSettings, $blockTypeSettings, $blockId);

		// Did it save?
		if (!$block->errors)
		{
			if (b()->request->isAjaxRequest)
			{
				$r = array(
					'success' => true,
					'id'      => $block->id,
					'name'    => $block->name,
					'type'    => $block->blockType->name
				);
				$this->returnJson($r);
			}
			else
			{
				b()->user->setMessage(MessageType::Notice, 'Content block saved.');
			}

			$url = b()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		if (b()->request->isAjaxRequest)
		{
			$r = array('errors' => $block->errors);
			$this->returnJson($r);
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save content block.');
		}

		// Reload the original template
		$this->loadRequestedTemplate(array('block' => $block));
	}
}
