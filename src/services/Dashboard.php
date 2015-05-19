<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\WidgetInterface;
use craft\app\errors\Exception;
use craft\app\errors\InvalidComponentException;
use craft\app\helpers\ComponentHelper;
use craft\app\records\Widget as WidgetRecord;
use craft\app\base\Widget;
use craft\app\widgets\Feed as FeedWidget;
use craft\app\widgets\GetHelp as GetHelpWidget;
use craft\app\widgets\InvalidWidget;
use craft\app\widgets\QuickPost as QuickPostWidget;
use craft\app\widgets\RecentEntries as RecentEntriesWidget;
use craft\app\widgets\Updates as UpdatesWidget;
use yii\base\Component;

/**
 * Class Dashboard service.
 *
 * An instance of the Dashboard service is globally accessible in Craft via [[Application::dashboard `Craft::$app->getDashboard()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Dashboard extends Component
{
	// Constants
	// =========================================================================

	/**
	 * @var string The widget interface name
	 */
	const WIDGET_INTERFACE = 'craft\app\base\WidgetInterface';

	// Public Methods
	// =========================================================================

	/**
	 * Returns all available widget type classes.
	 *
	 * @return WidgetInterface[] The available widget type classes.
	 */
	public function getAllWidgetTypes()
	{
		$widgetTypes = [
			FeedWidget::className(),
			GetHelpWidget::className(),
			QuickPostWidget::className(),
			RecentEntriesWidget::className(),
			UpdatesWidget::className(),
		];

		foreach (Craft::$app->getPlugins()->call('getWidgetTypes', [], true) as $pluginWidgetTypes)
		{
			$widgetTypes = array_merge($widgetTypes, $pluginWidgetTypes);
		}

		return $widgetTypes;
	}

	/**
	 * Creates a widget with a given config.
	 *
	 * @param mixed $config The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
	 * @return WidgetInterface|Widget
	 */
	public function createWidget($config)
	{
		if (is_string($config))
		{
			$config = ['type' => $config];
		}

		try
		{
			return ComponentHelper::createComponent($config, self::WIDGET_INTERFACE);
		}
		catch (InvalidComponentException $e)
		{
			$config['errorMessage'] = $e->getMessage();
			return InvalidWidget::create($config);
		}
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @param string|null $indexBy The attribute to index the widgets by
	 * @return WidgetInterface[]|Widget[] The widgets
	 */
	public function getAllWidgets($indexBy = null)
	{
		$widgets = $this->_getUserWidgetRecords($indexBy);

		// If there are no widget records, this is the first time they've hit the dashboard.
		if (!$widgets)
		{
			// Add the defaults and try again
			$this->_addDefaultUserWidgets();
			$widgets = $this->_getUserWidgetRecords($indexBy);
		}
		else
		{
			// Get only the enabled widgets.
			foreach ($widgets as $key => $widget)
			{
				if (!$widget->enabled)
				{
					unset($widgets[$key]);
				}
			}
		}

		foreach ($widgets as $key => $value)
		{
			$widgets[$key] = $this->createWidget($value);
		}

		return $widgets;
	}

	/**
	 * Returns whether the current user has a widget of the given type.
	 *
	 * @param string $type The widget type
	 * @return boolean Whether the current user has a widget of the given type
	 */
	public function doesUserHaveWidget($type)
	{
		return WidgetRecord::find()
			->where([
				'userId'  => Craft::$app->getUser()->getIdentity()->id,
				'type'    => $type,
				'enabled' => true
			])
			->exists();
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param integer $id The widget’s ID
	 * @return WidgetInterface|Widget|null The widget, or null if it doesn’t exist
	 */
	public function getWidgetById($id)
	{
		$widgetRecord = WidgetRecord::findOne([
			'id' => $id,
			'userId' => Craft::$app->getUser()->getIdentity()->id
		]);

		if ($widgetRecord)
		{
			return $this->createWidget($widgetRecord);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Saves a widget for the current user.
	 *
	 * @param WidgetInterface|Widget $widget   The widget to be saved
	 * @param boolean                $validate Whether the widget should be validated first
	 * @return boolean Whether the widget was saved successfully
	 * @throws \Exception
	 */
	public function saveWidget(WidgetInterface $widget, $validate = true)
	{
		if ((!$validate || $widget->validate()) && $widget->beforeSave())
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				$widgetRecord = $this->_getUserWidgetRecordById($widget->id);
				$isNewWidget = $widgetRecord->getIsNewRecord();

				$widgetRecord->type     = $widget->getType();
				$widgetRecord->settings = $widget->getSettings();

				// Enabled by default.
				if ($isNewWidget)
				{
					$widgetRecord->enabled = true;
				}

				$widgetRecord->save(false);

				// Now that we have a widget ID, save it on the model
				if ($isNewWidget)
				{
					$widget->id = $widgetRecord->id;
				}

				$widget->afterSave();

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

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Soft deletes a widget.
	 * @param integer $widgetId The widget’s ID
	 * @return boolean Whether the widget was deleted successfully
	 */
	public function deleteWidgetById($widgetId)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widgetId);
		$widgetRecord->enabled = false;
		$widgetRecord->save();

		return true;
	}

	/**
	 * Reorders widgets.
	 *
	 * @param integer[] $widgetIds The widget IDs
	 * @return boolean Whether the widgets were reordered successfully
	 * @throws \Exception
	 */
	public function reorderWidgets($widgetIds)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

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
	 */
	private function _addDefaultUserWidgets()
	{
		$user = Craft::$app->getUser()->getIdentity();

		// Recent Entries widget
		$this->saveWidget($this->createWidget(RecentEntriesWidget::className()));

		// Get Help widget
		if ($user->admin)
		{
			$this->saveWidget($this->createWidget(GetHelpWidget::className()));
		}

		// Updates widget
		if ($user->can('performupdates'))
		{
			$this->saveWidget($this->createWidget(UpdatesWidget::className()));
		}

		// Blog & Tonic feed widget
		$this->saveWidget($this->createWidget([
			'type'  => FeedWidget::className(),
			'url'   => 'http://feeds.feedburner.com/blogandtonic',
			'title' => 'Blog & Tonic'
		]));
	}

	/**
	 * Gets a widget's record.
	 *
	 * @param integer $widgetId
	 *
	 * @return WidgetRecord
	 */
	private function _getUserWidgetRecordById($widgetId = null)
	{
		$userId = Craft::$app->getUser()->getIdentity()->id;

		if ($widgetId)
		{
			$widgetRecord = WidgetRecord::findOne([
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
	 * @param integer $widgetId
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noWidgetExists($widgetId)
	{
		throw new Exception(Craft::t('app', 'No widget exists with the ID “{id}”.', ['id' => $widgetId]));
	}

	/**
	 * Returns the widget records for the current user.
	 *
	 * @param string $indexBy
	 * @return array
	 */
	private function _getUserWidgetRecords($indexBy = null)
	{
		return WidgetRecord::find()
			->where(['userId' => Craft::$app->getUser()->getIdentity()->id])
			->orderBy('sortOrder')
			->indexBy($indexBy)
			->all();
	}
}
