<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\helpers\ImageHelper;
use craft\app\models\AssetTransform;
use craft\app\web\Controller;

/**
 * The AssetTransformsController class is a controller that handles various actions related to asset transformations,
 * such as creating, editing and deleting transforms.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransformsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @throws HttpException if the user isn’t an admin
	 */
	public function init()
	{
		// All asset transform actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Shows the asset transform list.
	 *
	 * @return string The rendering result
	 */
	public function actionTransformIndex()
	{
		$variables['transforms'] = Craft::$app->getAssetTransforms()->getAllTransforms();
		$variables['transformModes'] = AssetTransform::getTransformModes();

		return $this->renderTemplate('settings/assets/transforms/_index', $variables);
	}

	/**
	 * Edit an asset transform.
	 *
	 * @param string         $transformHandle The transform’s handle, if any.
	 * @param AssetTransform $transform       The transform being edited, if there were any validation errors.
	 * @return string The rendering result
	 * @throws HttpException
	 */
	public function actionEditTransform($transformHandle = null, AssetTransform $transform = null)
	{
		if ($transform === null)
		{
			if ($transformHandle !== null)
			{
				$transform = Craft::$app->getAssetTransforms()->getTransformByHandle($transformHandle);

				if (!$transform)
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$transform = new AssetTransform();
			}
		}

		return $this->renderTemplate('settings/assets/transforms/_settings', [
			'handle' => $transformHandle,
			'transform' => $transform
		]);
	}

	/**
	 * Saves an asset source.
	 */
	public function actionSaveTransform()
	{
		$this->requirePostRequest();

		$transform = new AssetTransform();
		$transform->id = Craft::$app->getRequest()->getBodyParam('transformId');
		$transform->name = Craft::$app->getRequest()->getBodyParam('name');
		$transform->handle = Craft::$app->getRequest()->getBodyParam('handle');
		$transform->width = Craft::$app->getRequest()->getBodyParam('width');
		$transform->height = Craft::$app->getRequest()->getBodyParam('height');
		$transform->mode = Craft::$app->getRequest()->getBodyParam('mode');
		$transform->position = Craft::$app->getRequest()->getBodyParam('position');
		$transform->quality = Craft::$app->getRequest()->getBodyParam('quality');
		$transform->format = Craft::$app->getRequest()->getBodyParam('format');

		if (empty($transform->format))
		{
			$transform->format = null;
		}

		$errors = false;

		if (empty($transform->width) && empty($transform->height))
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'You must set at least one of the dimensions.'));
			$errors = true;
		}

		if (!empty($transform->quality) && (!is_numeric($transform->quality) || $transform->quality > 100 || $transform->quality < 1))
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Quality must be a number between 1 and 100 (included).'));
			$errors = true;
		}

		if (!empty($transform->format) && !in_array($transform->format, ImageHelper::getWebSafeFormats()))
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'That is not an allowed format.'));
			$errors = true;
		}

		if (!$errors)
		{
			// Did it save?
			if (Craft::$app->getAssetTransforms()->saveTransform($transform))
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Transform saved.'));
				return $this->redirectToPostedUrl($transform);
			}
			else
			{
				Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save source.'));
			}
		}

		// Send the transform back to the template
		Craft::$app->getUrlManager()->setRouteParams([
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

		$transformId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->getAssetTransforms()->deleteTransform($transformId);

		return $this->asJson(['success' => true]);
	}
}
