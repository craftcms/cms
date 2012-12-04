<?php
namespace Blocks;

/**
 * Handles asset size tasks
 */
class AssetSizesController extends BaseController
{
	/**
	 * Saves an asset source.
	 */
	public function actionSaveSize()
	{
		$this->requirePostRequest();

		$size = new AssetSizeModel();
		$size->id = blx()->request->getPost('sizeId');
		$size->name = blx()->request->getPost('name');
		$size->handle = blx()->request->getPost('handle');
		$size->width = blx()->request->getPost('width');
		$size->height = blx()->request->getPost('height');

		// Did it save?
		if (blx()->assetSizes->saveSize($size))
		{
			blx()->user->setNotice(Blocks::t('Size saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save source.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'size' => $size
		));
	}

	/**
	 * Deletes an asset size.
	 */
	public function actionDeleteSize()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sizeHandle = blx()->request->getRequiredPost('handle');

		blx()->assetSizes->deleteSizeByHandle($sizeHandle);

		$this->returnJson(array('success' => true));
	}
}
