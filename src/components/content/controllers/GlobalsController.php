<?php
namespace Blocks;

/**
 * Globals controller class
 */
class GlobalsController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return GlobalBlocksService
	 */
	protected function getService()
	{
		return blx()->globals;
	}

	/**
	 * Saves the global blocks.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();

		$content = new GlobalContentModel();
		$content->setContent(blx()->request->getPost('blocks'));

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$content->language = blx()->request->getPost('language');
		}
		else
		{
			$content->language = blx()->language;
		}

		if (blx()->globals->saveGlobalContent($content))
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
