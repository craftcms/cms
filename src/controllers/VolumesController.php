<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Volume;
use craft\app\elements\Asset;
use craft\app\errors\HttpException;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\web\Controller;

/**
 * The VolumeController class is a controller that handles various actions related to asset volumes, such as
 * creating, editing, renaming and reordering them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VolumesController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @throws HttpException if the user isn’t an admin
	 */
	public function init()
	{
		// All asset volume actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Shows the asset volume list.
	 *
	 * @return string The rendering result
	 */
	public function actionVolumeIndex()
	{
		$variables['volumes'] = Craft::$app->getVolumes()->getAllVolumes();
		return $this->renderTemplate('settings/assets/volumes/_index', $variables);
	}

	/**
	 * Edit an asset volume.
	 *
	 * @param int         $volumeId       The volume’s ID, if editing an existing volume.
	 * @param Volume $volume         The volume being edited, if there were any validation errors.
	 * @return string The rendering result
	 * @throws HttpException
	 */
	public function actionEditVolume($volumeId = null, Volume $volume = null)
	{
		$this->requireAdmin();

		if ($volume === null)
		{
			if ($volumeId !== null)
			{
				$volume = Craft::$app->getVolumes()->getVolumeById($volumeId);

				if (!$volume)
				{
					throw new HttpException(404, "No volume exists with the ID '$volumeId'.");
				}
			}
			else
			{
				$volume = Craft::$app->getVolumes()->createVolume('craft\app\volumes\Local');
			}
		}

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$allVolumeTypes = Craft::$app->getVolumes()->getAllVolumeTypes();
			$volumeInstances = [];
			$volumeTypeOptions = [];

			foreach ($allVolumeTypes as $class)
			{
				if ($class === $volume->getType() || $class::isSelectable())
				{
					$volumeInstances[$class] = Craft::$app->getVolumes()->createVolume($class);

					$volumeTypeOptions[] = [
						'value' => $class,
						'label' => $class::displayName()
					];
				}
			}
		}
		else
		{
			$volumeTypeOptions = [];
			$volumeInstances = [];
			$allVolumeTypes = null;
		}

		$isNewVolume = !$volume->id;

		if ($isNewVolume)
		{
			$title = Craft::t('app', 'Create a new asset volume');
		}
		else
		{
			$title = $volume->name;
		}

		$crumbs = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Assets'),   'url' => UrlHelper::getUrl('settings/assets')],
			['label' => Craft::t('app', 'Volumes'),  'url' => UrlHelper::getUrl('settings/assets')],
		];

		$tabs = [
			'settings'    => ['label' => Craft::t('app', 'Settings'),     'url' => '#assetvolume-settings'],
			'fieldlayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#assetvolume-fieldlayout'],
		];

		return $this->renderTemplate('settings/assets/volumes/_edit', [
			'volumeId' => $volumeId,
			'volume' => $volume,
			'isNewVolume' => $isNewVolume,
			'volumeTypes' => $allVolumeTypes,
			'volumeTypeOptions' => $volumeTypeOptions,
			'volumeInstances' => $volumeInstances,
			'title' => $title,
			'crumbs' => $crumbs,
			'tabs' => $tabs
		]);
	}

	/**
	 * Saves an asset volume.
	 *
	 * @return null
	 */
	public function actionSaveVolume()
	{
		$this->requirePostRequest();

		$request       = Craft::$app->getRequest();
		$volumeService = Craft::$app->getVolumes();

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$type = $request->getBodyParam('type');
		}
		else
		{
			$type = 'craft\app\volumes\Local';
		}

		$volume = $volumeService->createVolume([
			'id'       => $request->getBodyParam('volumeId'),
			'type'     => $type,
			'name'     => $request->getBodyParam('name'),
			'handle'   => $request->getBodyParam('handle'),
			'url'      => $request->getBodyParam('url'),
			'settings' => $request->getBodyParam('types.'.$type)
		]);

		// Set the field layout
		$fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = Asset::className();
		$volume->setFieldLayout($fieldLayout);

		if (Craft::$app->getVolumes()->saveVolume($volume))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Volume saved.'));
			return $this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save volume.'));
		}

		// Send the volume back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'volume' => $volume
		]);
	}

	/**
	 * Reorders asset volumes.
	 *
	 * @return null
	 */
	public function actionReorderVolumes()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$volumeIds = JsonHelper::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
		Craft::$app->getVolumes()->reorderVolumes($volumeIds);

		return $this->asJson(['success' => true]);
	}

	/**
	 * Deletes an asset volume.
	 *
	 * @return null
	 */
	public function actionDeleteVolume()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$volumeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->getVolumes()->deleteVolumeById($volumeId);

		return $this->asJson(['success' => true]);
	}

	/**
	 * Load Assets VolumeType data.
	 *
	 * This is used to, for example, load Amazon S3 bucket list or Rackspace Cloud Storage Containers.
	 *
	 * @return null
	 */
	public function actionLoadVolumeTypeData()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$volumeType = Craft::$app->getRequest()->getRequiredBodyParam('volumeType');
		$dataType   = Craft::$app->getRequest()->getRequiredBodyParam('dataType');
		$params     = Craft::$app->getRequest()->getBodyParam('params');

		$volumeType = 'craft\app\volumes\\'.$volumeType;

		if (!class_exists($volumeType))
		{
			return $this->asErrorJson(Craft::t('app', 'The volume type specified does not exist!'));
		}

		try
		{
			$result = call_user_func_array(array($volumeType, 'load'.ucfirst($dataType)), $params);
			return $this->asJson($result);
		}
		catch (\Exception $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}
	}
}
