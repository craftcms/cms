<?php
namespace Craft;

/**
 * The AssetSourcesController class is a controller that handles various actions related to asset sources, such as
 * creating, editing, renaming and reordering them.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.controllers
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
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
			$variables['sourceTypes'] = AssetSourceTypeVariable::populateVariables($sourceTypes);
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

		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Assets'),   'url' => UrlHelper::getUrl('settings/assets')),
			array('label' => Craft::t('Sources'),  'url' => UrlHelper::getUrl('settings/assets')),
		);

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'),     'url' => '#assetsource-settings'),
			'fieldlayout' => array('label' => Craft::t('Field Layout'), 'url' => '#assetsource-fieldlayout'),
		);

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
			$source->settings = array();
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
			$this->returnJson(S3AssetSourceType::getBucketList($keyId, $secret));
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

			/** @var RackspaceAssetSourceType $source */
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

			/** @var RackspaceAssetSourceType $source */
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
			$this->returnJson(GoogleCloudAssetSourceType::getBucketList($keyId, $secret));
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}
	}
}
