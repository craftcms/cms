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
	protected function populateBlockPackageFromPost()
	{
		$class = __NAMESPACE__.'\\'.$this->blockPackageClass;
		$blockPackage = new $class();

		$blockPackage->id = blx()->request->getPost('blockId');
		$blockPackage->name = blx()->request->getPost('name');
		$blockPackage->handle = blx()->request->getPost('handle');
		$blockPackage->instructions = blx()->request->getPost('instructions');
		/* BLOCKSPRO ONLY */
		$blockPackage->required = (bool)blx()->request->getPost('required');
		$blockPackage->translatable = (bool)blx()->request->getPost('translatable');
		/* end BLOCKSPRO ONLY */
		$blockPackage->class = blx()->request->getRequiredPost('class');

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$blockPackage->class]))
		{
			$blockPackage->settings = $typeSettings[$blockPackage->class];
		}

		return $blockPackage;
	}

	/**
	 * Saves a block.
	 */
	public function actionSaveBlock()
	{
		$this->requirePostRequest();

		$blockPackage = $this->populateBlockPackageFromPost();

		$service = $this->service;
		if (blx()->$service->saveBlock($blockPackage))
		{
			blx()->user->setNotice(Blocks::t('Block saved.'));
			$this->redirectToPostedUrl(array(
				'blockId' => $blockPackage->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save block.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'blockPackage' => $blockPackage
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
