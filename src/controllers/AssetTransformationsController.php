<?php
namespace Craft;

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
		$transformation->id = craft()->request->getPost('transformationId');
		$transformation->name = craft()->request->getPost('name');
		$transformation->handle = craft()->request->getPost('handle');
		$transformation->width = craft()->request->getPost('width');
		$transformation->height = craft()->request->getPost('height');
		$transformation->mode = craft()->request->getPost('mode');

		// Did it save?
		if (craft()->assetTransformations->saveTransformation($transformation))
		{
			craft()->userSession->setNotice(Craft::t('Transformation saved.'));
			$this->redirectToPostedUrl(array('handle' => $transformation->handle));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save source.'));
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

		$transformationId = craft()->request->getRequiredPost('id');

		craft()->assetTransformations->deleteTransformation($transformationId);

		$this->returnJson(array('success' => true));
	}
}
