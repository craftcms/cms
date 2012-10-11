<?php
namespace Blocks;

/**
 * Global blocks controller class
 */
class GlobalBlocksController extends BaseBlocksController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return GlobalBlocksService
	 */
	protected function getService()
	{
		return blx()->globalBlocks;
	}

	/**
	 * Saves the global blocks.
	 */
	public function actionSaveGlobalContent()
	{
		$this->requirePostRequest();

		$content = new GlobalContentModel();
		$content->setBlockValues(blx()->request->getPost('blocks'));

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$content->language = blx()->request->getPost('language');
		}
		else
		{
			$content->language = blx()->language;
		}

		if (blx()->globalBlocks->saveGlobalContent($content))
		{
			blx()->user->setNotice(blocks::t('Global blocks saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save global blocks.'));
			$this->renderRequestedTemplate(array(
				'globals' => $content,
			));
		}
	}
}
