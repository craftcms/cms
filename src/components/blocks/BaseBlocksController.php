<?php
namespace Blocks;

/**
 * Base blocks controller class
 */
abstract class BaseBlocksController extends BaseController
{
	/**
	 * Returns the block service instance.
	 *
	 * @abstract
	 * @return BaseBlocksService
	 */
	abstract protected function getService();

	/**
	 * Populates a block model from post.
	 *
	 * @access protected
	 * @return BaseBlockModel
	 */
	protected function populateBlockFromPost()
	{
		$block = $this->getService()->getNewBlock();

		$block->id = blx()->request->getPost('blockId');
		$block->name = blx()->request->getPost('name');
		$block->handle = blx()->request->getPost('handle');
		$block->instructions = blx()->request->getPost('instructions');
		$block->required = (bool)blx()->request->getPost('required');

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$block->translatable = (bool)blx()->request->getPost('translatable');
		}

		$block->type = blx()->request->getRequiredPost('type');

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$block->type]))
		{
			$block->settings = $typeSettings[$block->type];
		}

		return $block;
	}

	/**
	 * Saves a block.
	 */
	public function actionSaveBlock()
	{
		$this->requirePostRequest();

		$block = $this->populateBlockFromPost();

		if ($this->getService()->saveBlock($block))
		{
			blx()->user->setNotice(Blocks::t('Block saved.'));

			$this->redirectToPostedUrl(array(
				'blockId' => $block->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save block.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'block' => $block
		));
	}

	/**
	 * Deletes a block.
	 */
	public function actionDeleteBlock()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$blockId = blx()->request->getRequiredPost('id');
		$this->getService()->deleteBlockById($blockId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Reorders blocks.
	 */
	public function actionReorderBlocks()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$blockIds = JsonHelper::decode(blx()->request->getRequiredPost('ids'));
		$this->getService()->reorderBlocks($blockIds);

		$this->returnJson(array('success' => true));
	}
}
