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

		if (Craft::hasPackage(CraftPackage::Cloud))
		{
			$sourceTypes = craft()->assetSources->getAllSourceTypes();
			$variables['sourceTypes'] = AssetSourceTypeVariable::populateVariables($sourceTypes);
		}

		$this->renderTemplate('settings/assets/sources/_settings', $variables);
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

		if (Craft::hasPackage(CraftPackage::Cloud))
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
		Craft::requirePackage(CraftPackage::Cloud);

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
	 * Get Rackspace containers.
	 */
	public function actionGetRackspaceContainers()
	{
		craft()->userSession->requireAdmin();
		Craft::requirePackage(CraftPackage::Cloud);

		$username = craft()->request->getRequiredPost('username');
		$apiKey = craft()->request->getRequiredPost('apiKey');
		$location = craft()->request->getRequiredPost('location');

		try
		{
			// Static methods here are no-go (without passing unneeded variables around, such as location), we'll
			// have to mock up a SourceType object here.
			$model = new AssetSourceModel(array('type' => 'Rackspace', 'settings' => array('username' => $username, 'apiKey' => $apiKey, 'location' => $location)));

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
		Craft::requirePackage(CraftPackage::Cloud);

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
