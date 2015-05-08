<?php
namespace craft\app\services;

use Craft;
use craft\app\base\Volume;
use craft\app\base\VolumeInterface;
use craft\app\db\Query;
use craft\app\errors\ModelValidationException;
use craft\app\errors\VolumeException;
use craft\app\errors\InvalidComponentException;
use craft\app\helpers\ComponentHelper;
use craft\app\records\Volume as AssetVolumeRecord;
use craft\app\volumes\AwsS3;
use craft\app\volumes\GoogleCloud;
use craft\app\volumes\InvalidVolume;
use craft\app\volumes\Local;
use craft\app\volumes\Rackspace;
use yii\base\Component;

/**
 * Class AssetVolumesService
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.services
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
 */
class Volumes extends Component
{
	// Constants
	// =========================================================================

	/**
	 * @var string The field interface name
	 */
	const VOLUME_INTERFACE = 'craft\app\base\VolumeInterface';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allVolumeIds;

	/**
	 * @var
	 */
	private $_viewableVolumeIds;

	/**
	 * @var
	 */
	private $_viewableVolumes;

	/**
	 * @var
	 */
	private $_volumesById;

	/**
	 * @var bool
	 */
	private $_fetchedAllVolumes = false;

	// Public Methods
	// =========================================================================

	// Volumes
	// -------------------------------------------------------------------------

	/**
	 * Returns all available volume types.
	 *
	 * @return Volume[] the available volume type classes
	 */
	public function getAllVolumeTypes()
	{
		$volumeTypes = [
			Local::className()
		];

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$volumeTypes = array_merge($volumeTypes, [
				AwsS3::className(),
				GoogleCloud::className(),
				Rackspace::className(),
			]);
		}

		foreach (Craft::$app->getPlugins()->call('getVolumeTypes', [], true) as $pluginVolumeTypes)
		{
			$volumeTypes = array_merge($volumeTypes, $pluginVolumeTypes);
		}

		return $volumeTypes;
	}

	/**
	 * Returns all of the volume IDs.
	 *
	 * @return array
	 */
	public function getAllVolumeIds()
	{
		if (!isset($this->_allVolumeIds))
		{
			if ($this->_fetchedAllVolumes)
			{
				$this->_allVolumeIds = array_keys($this->_volumesById);
			}
			else
			{
				$this->_allVolumeIds = (new Query())
					->select('id')
					->from('{{%volumes}}')
					->column();
			}
		}

		return $this->_allVolumeIds;
	}

	/**
	 * Returns all volume IDs that are viewable by the current user.
	 *
	 * @return array
	 */
	public function getViewableVolumeIds()
	{
		if (!isset($this->_viewableVolumeIds))
		{
			$this->_viewableVolumeIds = array();

			foreach ($this->getAllVolumeIds() as $volumeId)
			{
				if (Craft::$app->user->checkPermission('viewAssetVolume:'.$volumeId))
				{
					$this->_viewableVolumeIds[] = $volumeId;
				}
			}
		}

		return $this->_viewableVolumeIds;
	}

	/**
	 * Returns all volumes that are viewable by the current user.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getViewableVolumes($indexBy = null)
	{
		if (!isset($this->_viewableVolumes))
		{
			$this->_viewableVolumes = array();

			foreach ($this->getAllVolumes() as $volume)
			{
				if (Craft::$app->user->checkPermission('viewAssetVolume:'.$volume->id))
				{
					$this->_viewableVolumes[] = $volume;
				}
			}
		}

		if (!$indexBy)
		{
			return $this->_viewableVolumes;
		}
		else
		{
			$volumes = array();

			foreach ($this->_viewableVolumes as $volume)
			{
				$volumes[$volume->$indexBy] = $volume;
			}

			return $volumes;
		}
	}

	/**
	 * Returns the total number of volumes
	 *
	 * @return int
	 */
	public function getTotalVolumes()
	{
		return count($this->getAllVolumeIds());
	}

	/**
	 * Returns the total number of volumes that are viewable by the current user.
	 *
	 * @return int
	 */
	public function getTotalViewableVolumes()
	{
		return count($this->getViewableVolumeIds());
	}

	/**
	 * Returns all volumes.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllVolumes($indexBy = null)
	{
		if (!$this->_fetchedAllVolumes)
		{
			$this->_volumesById = array();

			$results = $this->_createVolumeQuery()->all();

			foreach ($results as $result)
			{
				$volume = $this->createVolume($result);
				$this->_volumesById[$volume->id] = $volume;
			}

			$this->_fetchedAllVolumes = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_volumesById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_volumesById);
		}
		else
		{
			$volumes = array();

			foreach ($this->_volumesById as $volume)
			{
				$volumes[$volume->$indexBy] = $volume;
			}

			return $volumes;
		}
	}

	/**
	 * Returns a volume by its ID.
	 *
	 * @param int $volumeId
	 *
	 * @return Volume|null
	 */
	public function getVolumeById($volumeId)
	{
		// Temporary volume?
		if (is_null($volumeId))
		{
			// TODO TEMPORARY volume
			/*$volume = new AssetvolumeModel();
			$volume->id = $volumeId;
			$volume->name = TempAssetvolumeType::volumeName;
			$volume->type = TempAssetvolumeType::volumeType;
			$volume->settings = array('path' => Craft::$app->getPath()->getAssetsTempvolumePath(), 'url' => rtrim(UrlHelper::getResourceUrl(), '/').'/tempassets/');*/
			return;// $volume;
		}
		else
		{
			// If we've already fetched all volumes we can save ourselves a trip to the DB for volume IDs that don't
			// exist
			if (!$this->_fetchedAllVolumes &&
				(!isset($this->_volumesById) || !array_key_exists($volumeId, $this->_volumesById))
			)
			{
				$result = $this->_createVolumeQuery()
					->where('id = :id', array(':id' => $volumeId))
					->one();

				if ($result)
				{
					$volume = $this->createVolume($result);
				}
				else
				{
					$volume = null;
				}

				$this->_volumesById[$volumeId] = $volume;
			}

			if (!empty($this->_volumesById[$volumeId]))
			{
				return $this->_volumesById[$volumeId];
			}
		}

		return null;
	}

	/**
	 * Saves an asset volume.
	 *
	 * @param VolumeInterface|Volume $volume the Volume to be saved.
	 * @param boolean $validate      $validate Whether the volume should be validate first
	 * @return boolean Whether the field was saved successfully
	 * @throws \Exception
	 */

	public function saveVolume(VolumeInterface $volume, $validate = true)
	{
		if (!$validate || $volume->validate())
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

			try
			{
				$volumeRecord = $this->_getVolumeRecordById($volume->id);

				$isNewVolume = $volumeRecord->getIsNewRecord();

				$volumeRecord->name          = $volume->name;
				$volumeRecord->handle        = $volume->handle;
				$volumeRecord->type          = $volume->getType();
				$volumeRecord->url           = $volume->url;
				$volumeRecord->settings      = $volume->settings;
				$volumeRecord->fieldLayoutId = $volume->fieldLayoutId;

				if (!$isNewVolume)
				{
					Craft::$app->getFields()->deleteLayoutById($volumeRecord->fieldLayoutId);
				}
				else
				{
					// Set the sort order
					$maxSortOrder = (new Query())
						->select('max(sortOrder)')
						->from('{{%volumes}}')
						->scalar();

					$volumeRecord->sortOrder = $maxSortOrder + 1;
				}

				// Save the new one
				$fieldLayout = $volume->getFieldLayout();
				Craft::$app->getFields()->saveLayout($fieldLayout);

				// Update the volume record/model with the new layout ID
				$volume->fieldLayoutId = $fieldLayout->id;
				$volumeRecord->fieldLayoutId = $fieldLayout->id;

				// Save the volume
				$volumeRecord->save(false);

				if ($isNewVolume)
				{
					// Now that we have a volume ID, save it on the model
					$volume->id = $volumeRecord->id;
				}
				else
				{
					// Update the top folder's name with the volume's new name
					$topFolder = Craft::$app->getAssets()->findFolder(array('volumeId' => $volume->id, 'parentId' => ':empty:'));

					if ($topFolder !== null && $topFolder->name != $volume->name)
					{
						$topFolder->name = $volume->name;
						Craft::$app->getAssets()->storeFolderRecord($topFolder);
					}
				}

				Craft::$app->getAssetIndexer()->ensureTopFolder($volume);

				if ($transaction !== null)
				{
					$transaction->commit();
				}

				return true;
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Reorders asset volumes.
	 *
	 * @param array $volumeIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderVolumes($volumeIds)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			foreach ($volumeIds as $volumeOrder => $volumeId)
			{
				$volumeRecord = $this->_getVolumeRecordById($volumeId);
				$volumeRecord->sortOrder = $volumeOrder+1;
				$volumeRecord->save();
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Creates an asset volume with a given config.
	 *
	 * @param mixed $config The asset volume’s class name, or its config, with a `type` value and optionally a `settings` value
	 *
	 * @return VolumeInterface|Volume The asset volume
	 */
	public function createVolume($config)
	{
		if (is_string($config))
		{
			$config = ['type' => $config];
		}

		try
		{
			return ComponentHelper::createComponent($config, static::VOLUME_INTERFACE);
		}
		catch (InvalidComponentException $e)
		{
			$config['errorMessage'] = $e->getMessage();
			return InvalidVolume::create($config);
		}
	}

	/**
	 * Deletes an asset volume by its ID.
	 *
	 * @param int $volumeId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteVolumeById($volumeId)
	{
		if (!$volumeId)
		{
			return false;
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Grab the asset file ids so we can clean the elements table.
			$assetFileIds = (new Query())
				->select('id')
				->from('{{%assets}}')
				->where(array('volumeId' => $volumeId))
				->column();

			Craft::$app->getElements()->deleteElementById($assetFileIds);

			// Nuke the asset volume.
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%volumes}}', array('id' => $volumeId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving volumes.
	 *
	 * @return Query
	 */
	private function _createVolumeQuery()
	{
		return (new Query())
			->select('id, fieldLayoutId, name, handle, type, url, settings, sortOrder')
			->from('{{%volumes}}')
			->orderBy('sortOrder');
	}

	/**
	 * Gets a volume's record.
	 *
	 * @param int $volumeId
	 *
	 * @throws VolumeException
	 * @return AssetVolumeRecord
	 */
	private function _getVolumeRecordById($volumeId = null)
	{
		if ($volumeId)
		{
			$volumeRecord = AssetVolumeRecord::findOne(['id' => $volumeId]);

			if (!$volumeRecord)
			{
				throw new VolumeException(Craft::t('No volume exists with the ID “{id}”.', array('id' => $volumeId)));
			}
		}
		else
		{
			$volumeRecord = new AssetVolumeRecord();
		}

		return $volumeRecord;
	}
}
