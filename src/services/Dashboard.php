<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use craft\app\Craft;
use craft\app\errors\Exception;
use yii\base\Component;
use craft\app\enums\ComponentType;
use craft\app\models\Widget             as WidgetModel;
use craft\app\widgets\BaseWidget;
use craft\app\records\Widget            as WidgetRecord;
use craft\app\web\Application;

/**
 * Class Dashboard service.
 *
 * An instance of the Dashboard service is globally accessible in Craft via [[Application::dashboard `Craft::$app->dashboard`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Dashboard extends Component
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all installed widget types.
	 *
	 * @return array
	 */
	public function getAllWidgetTypes()
	{
		return Craft::$app->components->getComponentsByType(ComponentType::Widget);
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 *
	 * @return BaseWidget|null
	 */
	public function getWidgetType($class)
	{
		return Craft::$app->components->getComponentByTypeAndClass(ComponentType::Widget, $class);
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return BaseWidget|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		return Craft::$app->components->populateComponentByTypeAndModel(ComponentType::Widget, $widget);
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getUserWidgets($indexBy = null)
	{
		$widgetRecords = $this->_getUserWidgetRecords();

		// If there are no widget records, this is the first time they've hit the dashboard.
		if (!$widgetRecords)
		{
			// Add the defaults and try again
			$this->_addDefaultUserWidgets();
			$widgetRecords = $this->_getUserWidgetRecords();
		}
		else
		{
			// Get only the enabled widgets.
			foreach ($widgetRecords as $key => $widgetRecord)
			{
				if (!$widgetRecord->enabled)
				{
					unset($widgetRecords[$key]);
				}
			}
		}

		if (count($widgetRecords) > 0)
		{
			return WidgetModel::populateModels($widgetRecords, $indexBy);
		}

		return [];
	}

	/**
	 * Returns whether the current user has a widget of the given type.
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public function doesUserHaveWidget($type)
	{
		$count = WidgetRecord::model()->countByAttributes([
			'userId'  => Craft::$app->getUser()->getIdentity()->id,
			'type'    => $type,
			'enabled' => true
		]);

		return (bool)$count;
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 *
	 * @return WidgetModel
	 */
	public function getUserWidgetById($id)
	{
		$widgetRecord = WidgetRecord::model()->findByAttributes([
			'id' => $id,
			'userId' => Craft::$app->getUser()->getIdentity()->id
		]);

		if ($widgetRecord)
		{
			return WidgetModel::populateModel($widgetRecord);
		}
	}

	/**
	 * Saves a widget for the current user.
	 *
	 * @param WidgetModel $widget
	 *
	 * @return bool
	 */
	public function saveUserWidget(WidgetModel $widget)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widget->id);
		$widgetRecord->type = $widget->type;

		$widgetType = $this->populateWidgetType($widget);
		$processedSettings = $widgetType->prepSettings($widget->settings);
		$widgetRecord->settings = $widget->settings = $processedSettings;
		$widgetType->setSettings($processedSettings);

		$recordValidates = $widgetRecord->validate();
		$settingsValidate = $widgetType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			if ($widgetRecord->isNewRecord())
			{
				$maxSortOrder = Craft::$app->db->createCommand()
					->select('max(sortOrder)')
					->from('widgets')
					->where(['userId' => Craft::$app->getUser()->getIdentity()->id])
					->queryScalar();

				$widgetRecord->sortOrder = $maxSortOrder + 1;
			}

			$widgetRecord->save(false);

			// Now that we have a widget ID, save it on the model
			if (!$widget->id)
			{
				$widget->id = $widgetRecord->id;
			}

			return true;
		}
		else
		{
			$widget->addErrors($widgetRecord->getErrors());
			$widget->addSettingErrors($widgetType->getSettings()->getErrors());

			return false;
		}
	}

	/**
	 * Soft deletes a widget.
	 *
	 * @param int $widgetId
	 *
	 * @return bool
	 */
	public function deleteUserWidgetById($widgetId)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widgetId);
		$widgetRecord->enabled = false;
		$widgetRecord->save();

		return true;
	}

	/**
	 * Reorders widgets.
	 *
	 * @param array $widgetIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderUserWidgets($widgetIds)
	{
		$transaction = Craft::$app->db->getCurrentTransaction() === null ? Craft::$app->db->beginTransaction() : null;

		try
		{
			foreach ($widgetIds as $widgetOrder => $widgetId)
			{
				$widgetRecord = $this->_getUserWidgetRecordById($widgetId);
				$widgetRecord->sortOrder = $widgetOrder+1;
				$widgetRecord->save();
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

	// Private Methods
	// =========================================================================

	/**
	 * Adds the default widgets to the logged-in user.
	 *
	 * @return null
	 */
	private function _addDefaultUserWidgets()
	{
		$user = Craft::$app->getUser()->getIdentity();

		// Recent Entries widget
		$widget = new WidgetModel();
		$widget->type = 'RecentEntries';
		$this->saveUserWidget($widget);

		// Get Help widget
		if ($user->admin)
		{
			$widget = new WidgetModel();
			$widget->type = 'GetHelp';
			$this->saveUserWidget($widget);
		}

		// Updates widget
		if ($user->can('performupdates'))
		{
			$widget = new WidgetModel();
			$widget->type = 'Updates';
			$this->saveUserWidget($widget);
		}

		// Blog & Tonic feed widget
		$widget = new WidgetModel();
		$widget->type = 'Feed';
		$widget->settings = [
			'url'   => 'http://feeds.feedburner.com/blogandtonic',
			'title' => 'Blog & Tonic'
		];

		$this->saveUserWidget($widget);
	}

	/**
	 * Gets a widget's record.
	 *
	 * @param int $widgetId
	 *
	 * @return WidgetRecord
	 */
	private function _getUserWidgetRecordById($widgetId = null)
	{
		$userId = Craft::$app->getUser()->getIdentity()->id;

		if ($widgetId)
		{
			$widgetRecord = WidgetRecord::model()->findByAttributes([
				'id'     => $widgetId,
				'userId' => $userId
			]);

			if (!$widgetRecord)
			{
				$this->_noWidgetExists($widgetId);
			}
		}
		else
		{
			$widgetRecord = new WidgetRecord();
			$widgetRecord->userId = $userId;
		}

		return $widgetRecord;
	}

	/**
	 * Throws a "No widget exists" exception.
	 *
	 * @param int $widgetId
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noWidgetExists($widgetId)
	{
		throw new Exception(Craft::t('No widget exists with the ID “{id}”.', ['id' => $widgetId]));
	}

	/**
	 * Returns the widget records for the current user.
	 *
	 * @return array
	 */
	private function _getUserWidgetRecords()
	{
		return WidgetRecord::model()->ordered()->findAllByAttributes([
			'userId' => Craft::$app->getUser()->getIdentity()->id
		]);
	}
}
