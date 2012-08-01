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

		$blockSettings['name'] = blx()->request->getPost('name');
		$blockSettings['handle'] = blx()->request->getPost('handle');
		$blockSettings['class'] = blx()->request->getPost('class');
		$blockSettings['instructions'] = blx()->request->getPost('instructions');

		$blockTypeSettings = blx()->request->getPost($blockSettings['class']);
		$blockId = blx()->request->getPost('block_id');

		$block = blx()->blocks->saveBlock($blockSettings, $blockTypeSettings, $blockId);

		// Did it save?
		if (!$block->errors)
		{
			if (blx()->request->getIsAjaxRequest())
			{
				$r = array(
					'success' => true,
					'id'      => $block->id,
					'name'    => $block->name,
					'type'    => $block->blocktypeName
				);
				$this->returnJson($r);
			}
			else
			{
				blx()->user->setNotice('Content block saved.');
			}

			$this->redirectToPostedUrl();
		}

		if (blx()->request->getIsAjaxRequest())
		{
			$r = array('errors' => $block->errors);
			$this->returnJson($r);
		}
		else
		{
			blx()->user->setError('Couldn’t save content block.');
		}

		// Reload the original template
		$this->loadRequestedTemplate(array('block' => $block));
	}
}
