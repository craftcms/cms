<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\assetsourcetypes\S3;
use craft\app\Craft;
use craft\app\enums\ElementType;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\AssetSource         as AssetSourceModel;
use craft\app\errors\HttpException;
use craft\app\variables\AssetSourceType;

/**
 * The AssetSourcesController class is a controller that handles various actions related to asset sources, such as
 * creating, editing, renaming and reordering them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetSourcesController extends BaseController
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
		// All asset source actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Shows the asset source list.
	 *
	 * @return null
	 */
	public function actionSourceIndex()
	{
		$variables['sources'] = craft()->assetSources->getAllSources();
		$this->renderTemplate('settings/assets/sources/_index', $variables);
	}

	/**
	 * Edit an asset source.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditSource(array $variables = array())
	{
		if (empty($variables['source']))
		{
			if (!empty($variables['sourceId']))
			{
				$variables['source'] = craft()->assetSources->getSourceById($variables['sourceId']);
				if (!$variables['source'])
				{
					throw new HttpException(404);
				}
				$variables['sourceType'] = craft()->assetSources->populateSourceType($variables['source']);
			}
			else
			{
				$variables['source'] = new AssetSourceModel();
				$variables['sourceType'] = craft()->assetSources->getSourceType('Local');
			}
		}

		if (empty($variables['sourceType']))
		{
			$variables['sourceType'] = craft()->assetSources->populateSourceType($variables['source']);
		}

		if (craft()->getEdition() == Craft::Pro)
		{
			$sourceTypes = craft()->assetSources->getAllSourceTypes();
			$variables['sourceTypes'] = AssetSourceType::populateVariables($sourceTypes);
		}

		$variables['isNewSource'] = !$variables['source']->id;

		if ($variables['isNewSource'])
		{
			$variables['title'] = Craft::t('Create a new asset source');
		}
		else
		{
			$variables['title'] = $variables['source']->name;
		}

		$variables['crumbs'] = [
			['label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('Assets'),   'url' => UrlHelper::getUrl('settings/assets')],
			['label' => Craft::t('Sources'),  'url' => UrlHelper::getUrl('settings/assets')],
		];

		$variables['tabs'] = [
			'settings'    => ['label' => Craft::t('Settings'),     'url' => '#assetsource-settings'],
			'fieldlayout' => ['label' => Craft::t('Field Layout'), 'url' => '#assetsource-fieldlayout'],
		];

		$this->renderTemplate('settings/assets/sources/_edit', $variables);
	}

	/**
	 * Saves an asset source.
	 *
	 * @return null
	 */
	public function actionSaveSource()
	{
		$this->requirePostRequest();

		$existingSourceId = craft()->request->getPost('sourceId');

		if ($existingSourceId)
		{
			$source = craft()->assetSources->getSourceById($existingSourceId);
		}
		else
		{
			$source = new AssetSourceModel();
		}

		$source->name   = craft()->request->getPost('name');
		$source->handle = craft()->request->getPost('handle');

		if (craft()->getEdition() == Craft::Pro)
		{
			$source->type = craft()->request->getPost('type');
		}

		$typeSettings = craft()->request->getPost('types');
		if (isset($typeSettings[$source->type]))
		{
			if (!$source->settings)
			{
				$source->settings = array();
			}

			$source->settings = array_merge($source->settings, $typeSettings[$source->type]);
		}

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Asset;
		$source->setFieldLayout($fieldLayout);

		// Did it save?
		if (craft()->assetSources->saveSource($source))
		{
			craft()->userSession->setNotice(Craft::t('Source saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save source.'));
		}

		// Send the source back to the template
		craft()->urlManager->setRouteVariables(array(
			'source' => $source
		));
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

		$sourceIds = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		craft()->assetSources->reorderSources($sourceIds);

		$this->returnJson(array('success' => true));
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

		$sourceId = craft()->request->getRequiredPost('id');

		craft()->assetSources->deleteSourceById($sourceId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Get Amazon S3 buckets.
	 *
	 * @return null
	 */
	public function actionGetS3Buckets()
	{
		craft()->requireEdition(Craft::Pro);

		$keyId = craft()->request->getRequiredPost('keyId');
		$secret = craft()->request->getRequiredPost('secret');

		try
		{
			$this->returnJson(S3::getBucketList($keyId, $secret));
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}

	/**
	 * Get Rackspace regions.
	 *
	 * @return null
	 */
	public function actionGetRackspaceRegions()
	{
		craft()->requireEdition(Craft::Pro);

		$username = craft()->request->getRequiredPost('username');
		$apiKey = craft()->request->getRequiredPost('apiKey');

		try
		{
			// Static methods here are no-go (without passing unneeded variables around, such as location), we'll have
			// to mock up a SourceType object here.
			$model = new AssetSourceModel(array('type' => 'Rackspace', 'settings' => array('username' => $username, 'apiKey' => $apiKey)));

			/** @var \craft\app\assetsourcetypes\Rackspace $source */
			$source = craft()->assetSources->populateSourceType($model);
			$this->returnJson($source->getRegionList());
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}

	/**
	 * Get Rackspace containers.
	 *
	 * @return null
	 */
	public function actionGetRackspaceContainers()
	{
		craft()->requireEdition(Craft::Pro);

		$username = craft()->request->getRequiredPost('username');
		$apiKey = craft()->request->getRequiredPost('apiKey');
		$region = craft()->request->getRequiredPost('region');

		try
		{
			// Static methods here are no-go (without passing unneeded variables around, such as location), we'll have
			// to mock up a SourceType object here.
			$model = new AssetSourceModel(array('type' => 'Rackspace', 'settings' => array('username' => $username, 'apiKey' => $apiKey, 'region' => $region)));

			/** @var \craft\app\assetsourcetypes\Rackspace $source */
			$source = craft()->assetSources->populateSourceType($model);
			$this->returnJson($source->getContainerList());
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}

	/**
	 * Get Google Cloud Storage buckets.
	 *
	 * @return null
	 */
	public function actionGetGoogleCloudBuckets()
	{
		craft()->requireEdition(Craft::Pro);

		$keyId = craft()->request->getRequiredPost('keyId');
		$secret = craft()->request->getRequiredPost('secret');

		try
		{
			$this->returnJson(GoogleCloud::getBucketList($keyId, $secret));
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}
}
