<?php
namespace Craft;

/**
 * Handles asset source tasks
 */
class AssetSourcesController extends BaseController
{
	/**
	 * Shows the asset source list.
	 */
	public function actionSourceIndex()
	{
		craft()->userSession->requireAdmin();

		$variables['sources'] = craft()->assetSources->getAllSources();
		$this->renderTemplate('settings/assets/sources/index', $variables);
	}

	/**
	 * Edit an asset source.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditSource(array $variables = array())
	{
		craft()->userSession->requireAdmin();

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
	 */
	public function actionSaveSource()
	{
		craft()->userSession->requireAdmin();
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

		$source->name = craft()->request->getPost('name');

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
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
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
	 */
	public function actionReorderSources()
	{
		craft()->userSession->requireAdmin();
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceIds = JsonHelper::decode(craft()->request->getRequiredPost('ids'));
		craft()->assetSources->reorderSources($sourceIds);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Deletes an asset source.
	 */
	public function actionDeleteSource()
	{
		craft()->userSession->requireAdmin();
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceId = craft()->request->getRequiredPost('id');

		craft()->assetSources->deleteSourceById($sourceId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Get Amazon S3 buckets.
	 */
	public function actionGetS3Buckets()
	{
		craft()->userSession->requireAdmin();
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
	 */
	public function actionGetRackspaceRegions()
	{
		craft()->userSession->requireAdmin();
		craft()->requireEdition(Craft::Pro);

		$username = craft()->request->getRequiredPost('username');
		$apiKey = craft()->request->getRequiredPost('apiKey');

		try
		{
			// Static methods here are no-go (without passing unneeded variables around, such as location), we'll
			// have to mock up a SourceType object here.
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
	 */
	public function actionGetRackspaceContainers()
	{
		craft()->userSession->requireAdmin();
		craft()->requireEdition(Craft::Pro);

		$username = craft()->request->getRequiredPost('username');
		$apiKey = craft()->request->getRequiredPost('apiKey');
		$region = craft()->request->getRequiredPost('region');

		try
		{
			// Static methods here are no-go (without passing unneeded variables around, such as location), we'll
			// have to mock up a SourceType object here.
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
	 */
	public function actionGetGoogleCloudBuckets()
	{
		craft()->userSession->requireAdmin();
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
