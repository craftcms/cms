<?php
namespace Craft;

/**
 * Handles asset transform tasks
 */
class AssetTransformsController extends BaseController
{
	/**
	 * Saves an asset source.
	 */
	public function actionSaveTransform()
	{
		$this->requirePostRequest();

		$transform = new AssetTransformModel();
		$transform->id = craft()->request->getPost('transformId');
		$transform->name = craft()->request->getPost('name');
		$transform->handle = craft()->request->getPost('handle');
		$transform->width = craft()->request->getPost('width');
		$transform->height = craft()->request->getPost('height');
		$transform->mode = craft()->request->getPost('mode');

		if (empty($transform->width) && empty($transform->height))
		{
			craft()->userSession->setError(Craft::t('You must set at least one of the dimensions.'));
		}
		else
		{
			// Did it save?
			if (craft()->assetTransforms->saveTransform($transform))
			{
				craft()->userSession->setNotice(Craft::t('Transform saved.'));
				$this->redirectToPostedUrl(array('handle' => $transform->handle));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t save source.'));
			}
		}

		// Send the transform back to the template
		craft()->urlManager->setRouteVariables(array(
			'transform' => $transform
		));
	}

	/**
	 * Deletes an asset transform.
	 */
	public function actionDeleteTransform()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$transformId = craft()->request->getRequiredPost('id');

		craft()->assetTransforms->deleteTransform($transformId);

		$this->returnJson(array('success' => true));
	}
}
