<?php
namespace Blocks;

/**
 * Handles asset transformation tasks
 */
class AssetTransformationsController extends BaseController
{
	/**
	 * Saves an asset source.
	 */
	public function actionSaveTransformation()
	{
		$this->requirePostRequest();

		$transformation = new AssetTransformationModel();
		$transformation->id = blx()->request->getPost('transformationId');
		$transformation->name = blx()->request->getPost('name');
		$transformation->handle = blx()->request->getPost('handle');
		$transformation->width = blx()->request->getPost('width');
		$transformation->height = blx()->request->getPost('height');
		$transformation->mode = blx()->request->getPost('mode');

		// Did it save?
		if (blx()->assetTransformations->saveTransformation($transformation))
		{
			blx()->userSession->setNotice(Blocks::t('Transformation saved.'));
			$this->redirectToPostedUrl(array('handle' => $transformation->handle));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save source.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'transformation' => $transformation
		));
	}

	/**
	 * Deletes an asset transformation.
	 */
	public function actionDeleteTransformation()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$transformationHandle = blx()->request->getRequiredPost('handle');

		blx()->assetTransformations->deleteTransformationByHandle($transformationHandle);

		$this->returnJson(array('success' => true));
	}
}
