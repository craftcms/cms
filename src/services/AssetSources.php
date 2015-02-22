<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\assetsourcetypes\BaseAssetSourceType;
use craft\app\assetsourcetypes\Temp;
use craft\app\db\Command;
use craft\app\db\Query;
use craft\app\enums\ComponentType;
use craft\app\errors\Exception;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\AssetSource as AssetSourceModel;
use craft\app\records\AssetSource as AssetSourceRecord;
use yii\base\Component;

/**
 * Class AssetSources service.
 *
 * An instance of the AssetSources service is globally accessible in Craft via [[Application::assetSources `Craft::$app->assetSources`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetSources extends Component
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allSourceIds;

	/**
	 * @var
	 */
	private $_viewableSourceIds;

	/**
	 * @var
	 */
	private $_viewableSources;

	/**
	 * @var
	 */
	private $_sourcesById;

	/**
	 * @var bool
	 */
	private $_fetchedAllSources = false;

	// Public Methods
	// =========================================================================

	// Source Types
	// -------------------------------------------------------------------------

	/**
	 * Returns all available source types.
	 *
	 * @return array
	 */
	public function getAllSourceTypes()
	{
		if (Craft::$app->getEdition() == Craft::Pro)
		{
			return Craft::$app->components->getComponentsByType(ComponentType::AssetSource);
		}
		else
		{
			return [Craft::$app->components->getComponentByTypeAndClass(ComponentType::AssetSource, 'Local')];
		}
	}

	/**
	 * Returns an asset source type by its class handle.
	 *
	 * @param string $class
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function getSourceType($class)
	{
		return Craft::$app->components->getComponentByTypeAndClass(ComponentType::AssetSource, $class);
	}

	/**
	 * Populates an asset source type with a given source.
	 *
	 * @param AssetSourceModel $source
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function populateSourceType(AssetSourceModel $source)
	{
		return Craft::$app->components->populateComponentByTypeAndModel(ComponentType::AssetSource, $source);
	}

	/**
	 * Returns a source type by a given source ID.
	 *
	 * @param $sourceId
	 *
	 * @return BaseAssetSourceType
	 */
	public function getSourceTypeById($sourceId)
	{
		$source = $this->getSourceById($sourceId);
		return $this->populateSourceType($source);
	}

	// Sources
	// -------------------------------------------------------------------------

	/**
	 * Returns all of the source IDs.
	 *
	 * @return array
	 */
	public function getAllSourceIds()
	{
		if (!isset($this->_allSourceIds))
		{
			if ($this->_fetchedAllSources)
			{
				$this->_allSourceIds = array_keys($this->_sourcesById);
			}
			else
			{
				$this->_allSourceIds = (new Query())
					->select('id')
					->from('{{%assetsources}}')
					->column();
			}
		}

		return $this->_allSourceIds;
	}

	/**
	 * Returns all source IDs that are viewable by the current user.
	 *
	 * @return array
	 */
	public function getViewableSourceIds()
	{
		if (!isset($this->_viewableSourceIds))
		{
			$this->_viewableSourceIds = [];

			foreach ($this->getAllSourceIds() as $sourceId)
			{
				if (Craft::$app->getUser()->checkPermission('viewAssetSource:'.$sourceId))
				{
					$this->_viewableSourceIds[] = $sourceId;
				}
			}
		}

		return $this->_viewableSourceIds;
	}

	/**
	 * Returns all sources that are viewable by the current user.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getViewableSources($indexBy = null)
	{
		if (!isset($this->_viewableSources))
		{
			$this->_viewableSources = [];

			foreach ($this->getAllSources() as $source)
			{
				if (Craft::$app->getUser()->checkPermission('viewAssetSource:'.$source->id))
				{
					$this->_viewableSources[] = $source;
				}
			}
		}

		if (!$indexBy)
		{
			return $this->_viewableSources;
		}
		else
		{
			$sources = [];

			foreach ($this->_viewableSources as $source)
			{
				$sources[$source->$indexBy] = $source;
			}

			return $sources;
		}
	}

	/**
	 * Returns the total number of sources
	 *
	 * @return int
	 */
	public function getTotalSources()
	{
		return count($this->getAllSourceIds());
	}

	/**
	 * Returns the total number of sources that are viewable by the current user.
	 *
	 * @return int
	 */
	public function getTotalViewableSources()
	{
		return count($this->getViewableSourceIds());
	}

	/**
	 * Returns all sources.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllSources($indexBy = null)
	{
		if (!$this->_fetchedAllSources)
		{
			$this->_sourcesById = [];

			$results = $this->_createSourceQuery()->queryAll();

			foreach ($results as $result)
			{
				$source = $this->_populateSource($result);
				$this->_sourcesById[$source->id] = $source;
			}

			$this->_fetchedAllSources = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_sourcesById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_sourcesById);
		}
		else
		{
			$sources = [];

			foreach ($this->_sourcesById as $source)
			{
				$sources[$source->$indexBy] = $source;
			}

			return $sources;
		}
	}

	/**
	 * Returns a source by its ID.
	 *
	 * @param int $sourceId
	 *
	 * @return AssetSourceModel|null
	 */
	public function getSourceById($sourceId)
	{
		// Temporary source?
		if (is_null($sourceId))
		{
			$source = new AssetSourceModel();
			$source->id = $sourceId;
			$source->name = Temp::sourceName;
			$source->type = Temp::sourceType;
			$source->settings = ['path' => Craft::$app->path->getAssetsTempSourcePath().'/', 'url' => UrlHelper::getResourceUrl('tempassets').'/'];
			return $source;
		}
		else
		{
			// If we've already fetched all sources we can save ourselves a trip to the DB for source IDs that don't
			// exist
			if (!$this->_fetchedAllSources &&
				(!isset($this->_sourcesById) || !array_key_exists($sourceId, $this->_sourcesById))
			)
			{
				$result = $this->_createSourceQuery()
					->where('id = :id', [':id' => $sourceId])
					->queryRow();

				if ($result)
				{
					$source = $this->_populateSource($result);
				}
				else
				{
					$source = null;
				}

				$this->_sourcesById[$sourceId] = $source;
			}

			if (!empty($this->_sourcesById[$sourceId]))
			{
				return $this->_sourcesById[$sourceId];
			}
		}
	}

	/**
	 * Saves an asset source.
	 *
	 * @param AssetSourceModel $source
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSource(AssetSourceModel $source)
	{
		$sourceRecord = $this->_getSourceRecordById($source->id);

		$isNewSource = $sourceRecord->getIsNewRecord();

		if (!$isNewSource)
		{
			$oldSource = AssetSourceModel::populateModel($sourceRecord);
		}

		$sourceRecord->name          = $source->name;
		$sourceRecord->handle        = $source->handle;
		$sourceRecord->type          = $source->type;
		$sourceRecord->fieldLayoutId = $source->fieldLayoutId;

		$sourceType = $this->populateSourceType($source);
		$processedSettings = $sourceType->prepSettings($source->settings);
		$sourceRecord->settings = $source->settings = $processedSettings;
		$sourceType->setSettings($processedSettings);

		$recordValidates = $sourceRecord->validate();
		$settingsValidate = $sourceType->getSettings()->validate();
		$sourceErrors = $sourceType->getSourceErrors();


		if ($recordValidates && $settingsValidate && empty($sourceErrors))
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				if ($isNewSource)
				{
					// Set the sort order
					$maxSortOrder = (new Query())
						->from('{{%assetsources}}')
						->max('sortOrder');

					$sourceRecord->sortOrder = $maxSortOrder + 1;
				}

				if (!$isNewSource && $oldSource->fieldLayoutId)
				{
					// Drop the old field layout
					Craft::$app->fields->deleteLayoutById($oldSource->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $source->getFieldLayout();
				Craft::$app->fields->saveLayout($fieldLayout);

				// Update the source record/model with the new layout ID
				$source->fieldLayoutId = $fieldLayout->id;
				$sourceRecord->fieldLayoutId = $fieldLayout->id;

				// Save the source
				$sourceRecord->save(false);

				if ($isNewSource)
				{
					// Now that we have a source ID, save it on the model
					$source->id = $sourceRecord->id;
				}
				else
				{
					// Update the top folder's name with the source's new name
					$topFolder = Craft::$app->assets->findFolder(['sourceId' => $source->id, 'parentId' => ':empty:']);

					if ($topFolder->name != $source->name)
					{
						$topFolder->name = $source->name;
						Craft::$app->assets->storeFolder($topFolder);
					}
				}

				Craft::$app->assetIndexing->ensureTopFolder($source);

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
		else
		{
			$source->addErrors($sourceRecord->getErrors());
			$source->addSettingErrors($sourceType->getSettings()->getErrors());
			$source->addSettingErrors($sourceErrors);

			return false;
		}
	}

	/**
	 * Reorders asset sources.
	 *
	 * @param array $sourceIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderSources($sourceIds)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			foreach ($sourceIds as $sourceOrder => $sourceId)
			{
				$sourceRecord = $this->_getSourceRecordById($sourceId);
				$sourceRecord->sortOrder = $sourceOrder+1;
				$sourceRecord->save();
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
	 * Deletes an asset source by its ID.
	 *
	 * @param int $sourceId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteSourceById($sourceId)
	{
		if (!$sourceId)
		{
			return false;
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Grab the asset file ids so we can clean the elements table.
			$assetFileIds = Craft::$app->getDb()->createCommand()
				->select('id')
				->from('{{%assetfiles}}')
				->where(['sourceId' => $sourceId])
				->queryColumn();

			Craft::$app->elements->deleteElementById($assetFileIds);

			// Nuke the asset source.
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%assetsources}}', ['id' => $sourceId]);

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
	 * Returns a Command object prepped for retrieving sources.
	 *
	 * @return Command
	 */
	private function _createSourceQuery()
	{
		return Craft::$app->getDb()->createCommand()
			->select('id, fieldLayoutId, name, handle, type, settings, sortOrder')
			->from('{{%assetsources}}')
			->orderBy('sortOrder');
	}

	/**
	 * Populates a source from its DB result.
	 *
	 * @param array $result
	 *
	 * @return AssetSourceModel
	 */
	private function _populateSource($result)
	{
		if ($result['settings'])
		{
			$result['settings'] = JsonHelper::decode($result['settings']);
		}

		return new AssetSourceModel($result);
	}

	/**
	 * Gets a source's record.
	 *
	 * @param int $sourceId
	 *
	 * @throws Exception
	 * @return AssetSourceRecord
	 */
	private function _getSourceRecordById($sourceId = null)
	{
		if ($sourceId)
		{
			$sourceRecord = AssetSourceRecord::findOne($sourceId);

			if (!$sourceRecord)
			{
				throw new Exception(Craft::t('app', 'No source exists with the ID “{id}”.', ['id' => $sourceId]));
			}
		}
		else
		{
			$sourceRecord = new AssetSourceRecord();
		}

		return $sourceRecord;
	}
}
