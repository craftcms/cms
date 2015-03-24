<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\elements\Asset;
use craft\app\errors\ModelException;
use craft\app\errors\HttpException;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\AssetSource;
use craft\app\variables\AssetSourceType;
use craft\app\web\Controller;

/**
 * The AssetSourcesController class is a controller that handles various actions related to asset sources, such as
 * creating, editing, renaming and reordering them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetSourcesController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc Controller::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All asset source actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Shows the asset source list.
	 *
	 * @return null
	 */
	public function actionSourceIndex()
	{
		$variables['sources'] = Craft::$app->assetSources->getAllSources();
		$this->renderTemplate('settings/assets/sources/_index', $variables);
	}

	/**
	 * Edit an asset source.
	 *
	 * @param int         $sourceId       The source’s ID, if editing an existing source.
	 * @param AssetSource $source         The source being edited, if there were any validation errors.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditSource($sourceId = null, AssetSource $source = null)
	{
		if ($source === null)
		{
			if ($sourceId !== null)
			{
				$source = Craft::$app->assetSources->getSourceById($sourceId);

				if (!$source)
				{
					throw new HttpException(404);
				}

				$sourceType = Craft::$app->assetSources->populateSourceType($source);
			}
			else
			{
				$source = new AssetSource();
				$sourceType = Craft::$app->assetSources->getSourceType('Local');
			}
		}

		if (empty($sourceType))
		{
			$sourceType = Craft::$app->assetSources->populateSourceType($source);
		}

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$sourceTypes = Craft::$app->assetSources->getAllSourceTypes();
			$sourceTypes = AssetSourceType::populateVariables($sourceTypes);
		}
		else
		{
			$sourceTypes = null;
		}

		$isNewSource = !$source->id;

		if ($isNewSource)
		{
			$title = Craft::t('app', 'Create a new asset source');
		}
		else
		{
			$title = $source->name;
		}

		$crumbs = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Assets'),   'url' => UrlHelper::getUrl('settings/assets')],
			['label' => Craft::t('app', 'Sources'),  'url' => UrlHelper::getUrl('settings/assets')],
		];

		$tabs = [
			'settings'    => ['label' => Craft::t('app', 'Settings'),     'url' => '#assetsource-settings'],
			'fieldlayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#assetsource-fieldlayout'],
		];

		$this->renderTemplate('settings/assets/sources/_edit', [
			'sourceId' => $sourceId,
			'source' => $source,
			'isNewSource' => $isNewSource,
			'sourceTypes' => $sourceTypes,
			'sourceType' => $sourceType,
			'title' => $title,
			'crumbs' => $crumbs,
			'tabs' => $tabs
		]);
	}

	/**
	 * Saves an asset source.
	 *
	 * @return null
	 */
	public function actionSaveSource()
	{
		$this->requirePostRequest();

		$existingSourceId = Craft::$app->getRequest()->getBodyParam('sourceId');

		if ($existingSourceId)
		{
			$source = Craft::$app->assetSources->getSourceById($existingSourceId);
		}
		else
		{
			$source = new AssetSource();
		}

		$source->name   = Craft::$app->getRequest()->getBodyParam('name');
		$source->handle = Craft::$app->getRequest()->getBodyParam('handle');
		$source->url    = Craft::$app->getRequest()->getBodyParam('url');

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$source->type = Craft::$app->getRequest()->getBodyParam('type');
		}

		$typeSettings = Craft::$app->getRequest()->getBodyParam('types');
		if (isset($typeSettings[$source->type]))
		{
			if (!$source->settings)
			{
				$source->settings = [];
			}

			$source->settings = array_merge($source->settings, $typeSettings[$source->type]);
		}

		// Set the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = Asset::className();
		$source->setFieldLayout($fieldLayout);

		try
		{
			Craft::$app->assetSources->saveSource($source);
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Source saved.'));
			$this->redirectToPostedUrl();
		}
		catch (ModelException $exception)
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save source.'));
		}

		// Send the source back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'source' => $source
		]);
	}

	/**
	 * Reorders asset sources.
	 *
	 * @return null
	 */
	public function actionReorderSources()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceIds = JsonHelper::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
		Craft::$app->assetSources->reorderSources($sourceIds);

		$this->returnJson(['success' => true]);
	}

	/**
	 * Deletes an asset source.
	 *
	 * @return null
	 */
	public function actionDeleteSource()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->assetSources->deleteSourceById($sourceId);

		$this->returnJson(['success' => true]);
	}

	/**
	 * Load Assets SourceType data.
	 *
	 * This is used to, for example, load Amazon S3 bucket list or Rackspace Cloud Storage Containers.
	 *
	 * @return null
	 */
	public function actionLoadSourceTypeData()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceType = Craft::$app->getRequest()->getRequiredBodyParam('sourceType');
		$dataType   = Craft::$app->getRequest()->getRequiredBodyParam('dataType');
		$params     = Craft::$app->getRequest()->getBodyParam('params');

		$sourceType = 'craft\app\assetsourcetypes\\'.$sourceType;

		if (!class_exists($sourceType))
		{
			$this->returnErrorJson(Craft::t('app', 'The source type specified does not exist!'));
		}

		try
		{
			$this->returnJson(call_user_func(array($sourceType, "loadSourceTypeData"), $dataType, $params));
		}
		catch (\Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}
}
