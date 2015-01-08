<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\helpers\ImageHelper;
use craft\app\models\AssetTransform as AssetTransformModel;
use craft\app\errors\HttpException;

/**
 * The AssetTransformsController class is a controller that handles various actions related to asset transformations,
 * such as creating, editing and deleting transforms.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransformsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All asset transform actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Shows the asset transform list.
	 */
	public function actionTransformIndex()
	{
		$variables['transforms'] = Craft::$app->assetTransforms->getAllTransforms();
		$variables['transformModes'] = AssetTransformModel::getTransformModes();

		$this->renderTemplate('settings/assets/transforms/_index', $variables);
	}

	/**
	 * Edit an asset transform.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditTransform(array $variables = [])
	{
		if (empty($variables['transform']))
		{
			if (!empty($variables['handle']))
			{
				$variables['transform'] = Craft::$app->assetTransforms->getTransformByHandle($variables['handle']);
				if (!$variables['transform'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['transform'] = new AssetTransformModel();
			}
		}

		$this->renderTemplate('settings/assets/transforms/_settings', $variables);
	}

	/**
	 * Saves an asset source.
	 */
	public function actionSaveTransform()
	{
		$this->requirePostRequest();

		$transform = new AssetTransformModel();
		$transform->id = Craft::$app->request->getPost('transformId');
		$transform->name = Craft::$app->request->getPost('name');
		$transform->handle = Craft::$app->request->getPost('handle');
		$transform->width = Craft::$app->request->getPost('width');
		$transform->height = Craft::$app->request->getPost('height');
		$transform->mode = Craft::$app->request->getPost('mode');
		$transform->position = Craft::$app->request->getPost('position');
		$transform->quality = Craft::$app->request->getPost('quality');
		$transform->format = Craft::$app->request->getPost('format');

		if (empty($transform->format))
		{
			$transform->format = null;
		}

		$errors = false;

		if (empty($transform->width) && empty($transform->height))
		{
			Craft::$app->getSession()->setError(Craft::t('You must set at least one of the dimensions.'));
			$errors = true;
		}

		if (!empty($transform->quality) && (!is_numeric($transform->quality) || $transform->quality > 100 || $transform->quality < 1))
		{
			Craft::$app->getSession()->setError(Craft::t('Quality must be a number between 1 and 100 (included).'));
			$errors = true;
		}

		if (!empty($transform->format) && !in_array($transform->format, ImageHelper::getWebSafeFormats()))
		{
			Craft::$app->getSession()->setError(Craft::t('That is not an allowed format.'));
			$errors = true;
		}

		if (!$errors)
		{
			// Did it save?
			if (Craft::$app->assetTransforms->saveTransform($transform))
			{
				Craft::$app->getSession()->setNotice(Craft::t('Transform saved.'));
				$this->redirectToPostedUrl($transform);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('Couldn’t save source.'));
			}
		}

		// Send the transform back to the template
		Craft::$app->urlManager->setRouteVariables([
			'transform' => $transform
		]);
	}

	/**
	 * Deletes an asset transform.
	 */
	public function actionDeleteTransform()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$transformId = Craft::$app->request->getRequiredPost('id');

		Craft::$app->assetTransforms->deleteTransform($transformId);

		$this->returnJson(['success' => true]);
	}
}
