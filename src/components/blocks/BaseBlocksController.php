<?php
namespace Blocks;

/**
 * Base blocks controller class
 */
abstract class BaseBlocksController extends BaseController
{
	protected $blockPackageClass;
	protected $service;

	/**
	 * Populates a block package from post.
	 *
	 * @access protected
	 * @return BaseBlockPackage
	 */
	protected function populateBlockFromPost()
	{
		$class = __NAMESPACE__.'\\'.$this->blockPackageClass;
		$block = new $class();

		$block->id = blx()->request->getPost('blockId');
		$block->name = blx()->request->getPost('name');
		$block->handle = blx()->request->getPost('handle');
		$block->instructions = blx()->request->getPost('instructions');
		/* BLOCKSPRO ONLY */
		$block->required = (bool)blx()->request->getPost('required');
		$block->translatable = (bool)blx()->request->getPost('translatable');
		/* end BLOCKSPRO ONLY */
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

		$service = $this->service;
		if (blx()->$service->saveBlock($block))
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

		$blockId = blx()->request->getRequiredPost('blockId');

		$service = $this->service;
		blx()->$service->deleteBlockById($blockId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Reorders blocks.
	 */
	public function actionReorderBlocks()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$blockIds = JsonHelper::decode(blx()->request->getRequiredPost('blockIds'));

		$service = $this->service;
		blx()->$service->reorderBlocks($blockIds);

		$this->returnJson(array('success' => true));
	}
}
