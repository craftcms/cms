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
		craft()->userSession->requireAdmin();
	}

	/**
	 * Shows the asset transform list.
	 */
	public function actionTransformIndex()
	{
		$variables['transforms'] = craft()->assetTransforms->getAllTransforms();
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
	public function actionEditTransform(array $variables = array())
	{
		if (empty($variables['transform']))
		{
			if (!empty($variables['handle']))
			{
				$variables['transform'] = craft()->assetTransforms->getTransformByHandle($variables['handle']);
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
		$transform->id = craft()->request->getPost('transformId');
		$transform->name = craft()->request->getPost('name');
		$transform->handle = craft()->request->getPost('handle');
		$transform->width = craft()->request->getPost('width');
		$transform->height = craft()->request->getPost('height');
		$transform->mode = craft()->request->getPost('mode');
		$transform->position = craft()->request->getPost('position');
		$transform->quality = craft()->request->getPost('quality');
		$transform->format = craft()->request->getPost('format');

		if (empty($transform->format))
		{
			$transform->format = null;
		}

		$errors = false;

		if (empty($transform->width) && empty($transform->height))
		{
			craft()->userSession->setError(Craft::t('You must set at least one of the dimensions.'));
			$errors = true;
		}

		if (!empty($transform->quality) && (!is_numeric($transform->quality) || $transform->quality > 100 || $transform->quality < 1))
		{
			craft()->userSession->setError(Craft::t('Quality must be a number between 1 and 100 (included).'));
			$errors = true;
		}

		if (!empty($transform->format) && !in_array($transform->format, ImageHelper::getWebSafeFormats()))
		{
			craft()->userSession->setError(Craft::t('That is not an allowed format.'));
			$errors = true;
		}

		if (!$errors)
		{
			// Did it save?
			if (craft()->assetTransforms->saveTransform($transform))
			{
				craft()->userSession->setNotice(Craft::t('Transform saved.'));
				$this->redirectToPostedUrl($transform);
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
