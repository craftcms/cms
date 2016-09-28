<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\WidgetInterface;
use craft\app\db\Query;
use craft\app\errors\MissingComponentException;
use craft\app\errors\WidgetNotFoundException;
use craft\app\events\WidgetEvent;
use craft\app\helpers\Component as ComponentHelper;
use craft\app\records\Widget as WidgetRecord;
use craft\app\base\Widget;
use craft\app\widgets\Feed as FeedWidget;
use craft\app\widgets\GetHelp as GetHelpWidget;
use craft\app\widgets\MissingWidget;
use craft\app\widgets\QuickPost as QuickPostWidget;
use craft\app\widgets\RecentEntries as RecentEntriesWidget;
use craft\app\widgets\Updates as UpdatesWidget;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Dashboard service.
 *
 * An instance of the Dashboard service is globally accessible in Craft via [[Application::dashboard `Craft::$app->getDashboard()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Dashboard extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event WidgetEvent The event that is triggered before a widget is saved.
     */
    const EVENT_BEFORE_SAVE_WIDGET = 'beforeSaveWidget';

    /**
     * @event WidgetEvent The event that is triggered after a widget is saved.
     */
    const EVENT_AFTER_SAVE_WIDGET = 'afterSaveWidget';

    /**
     * @event WidgetEvent The event that is triggered before a widget is deleted.
     */
    const EVENT_BEFORE_DELETE_WIDGET = 'beforeDeleteWidget';

    /**
     * @event WidgetEvent The event that is triggered after a widget is deleted.
     */
    const EVENT_AFTER_DELETE_WIDGET = 'afterDeleteWidget';

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
            FeedWidget::class,
            GetHelpWidget::class,
            QuickPostWidget::class,
            RecentEntriesWidget::class,
            UpdatesWidget::class,
        ];

        foreach (Craft::$app->getPlugins()->call('getWidgetTypes', [], true) as $pluginWidgetTypes) {
            $widgetTypes = array_merge($widgetTypes, $pluginWidgetTypes);
        }

        return $widgetTypes;
    }

    /**
     * Creates a widget with a given config.
     *
     * @param mixed $config The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
     *
     * @return WidgetInterface
     */
    public function createWidget($config)
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            return ComponentHelper::createComponent($config, WidgetInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();

            return MissingWidget::create($config);
        }
    }

    /**
     * Returns the dashboard widgets for the current user.
     *
     * @param string|null $indexBy The attribute to index the widgets by
     *
     * @return WidgetInterface[] The widgets
     */
    public function getAllWidgets($indexBy = null)
    {
        $widgets = $this->_getUserWidgets($indexBy);

        // If there are no widgets, this is the first time they've hit the dashboard.
        if (!$widgets) {
            // Add the defaults and try again
            $this->_addDefaultUserWidgets();
            $widgets = $this->_getUserWidgets($indexBy);
        }

        return $widgets;
    }

    /**
     * Returns whether the current user has a widget of the given type.
     *
     * @param string $type The widget type
     *
     * @return boolean Whether the current user has a widget of the given type
     */
    public function doesUserHaveWidget($type)
    {
        return WidgetRecord::find()
            ->where([
                'userId' => Craft::$app->getUser()->getIdentity()->id,
                'type' => $type,
                'enabled' => true
            ])
            ->exists();
    }

    /**
     * Returns a widget by its ID.
     *
     * @param integer $id The widget’s ID
     *
     * @return WidgetInterface|null The widget, or null if it doesn’t exist
     */
    public function getWidgetById($id)
    {
        $widgetRecord = WidgetRecord::findOne([
            'id' => $id,
            'userId' => Craft::$app->getUser()->getIdentity()->id
        ]);

        if ($widgetRecord) {
            return $this->createWidget($widgetRecord);
        }

        return null;
    }

    /**
     * Saves a widget for the current user.
     *
     * @param WidgetInterface $widget        The widget to be saved
     * @param boolean         $runValidation Whether the widget should be validated
     *
     * @return boolean Whether the widget was saved successfully
     * @throws \Exception if reasons
     */
    public function saveWidget(WidgetInterface $widget, $runValidation = true)
    {
        /** @var Widget $widget */
        if ($runValidation && !$widget->validate()) {
            Craft::info('Widget not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewWidget = $widget->getIsNew();

        // Fire a 'beforeSaveWidget' event
        $this->trigger(self::EVENT_BEFORE_SAVE_WIDGET, new WidgetEvent([
            'widget' => $widget,
            'isNew' => $isNewWidget,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$widget->beforeSave()) {
                $transaction->rollBack();

                return false;
            }

            $widgetRecord = $this->_getUserWidgetRecordById($widget->id);

            $widgetRecord->type = $widget->getType();
            $widgetRecord->settings = $widget->getSettings();

            // Enabled by default.
            if ($isNewWidget) {
                $widgetRecord->enabled = true;
            }

            $widgetRecord->save(false);

            // Now that we have a widget ID, save it on the model
            if ($isNewWidget) {
                $widget->id = $widgetRecord->id;
            }

            $widget->afterSave();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveWidget' event
        $this->trigger(self::EVENT_AFTER_SAVE_WIDGET, new WidgetEvent([
            'widget' => $widget,
            'isNew' => $isNewWidget,
        ]));

        return true;
    }

    /**
     * Soft-deletes a widget by its ID.
     *
     * @param integer $widgetId The widget’s ID
     *
     * @return boolean Whether the widget was deleted successfully
     */
    public function deleteWidgetById($widgetId)
    {
        $widget = $this->getWidgetById($widgetId);

        if (!$widget) {
            return false;
        }

        return $this->deleteWidget($widget);
    }

    /**
     * Soft-deletes a widget.
     *
     * @param WidgetInterface $widget The widget to be deleted
     *
     * @return boolean Whether the widget was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteWidget(WidgetInterface $widget)
    {
        /** @var Widget $widget */
        // Fire a 'beforeDeleteWidget' event
        $this->trigger(self::EVENT_BEFORE_DELETE_WIDGET, new WidgetEvent([
            'widget' => $widget,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$widget->beforeDelete()) {
                $transaction->rollBack();

                return false;
            }

            $widgetRecord = $this->_getUserWidgetRecordById($widget->id);
            $widgetRecord->enabled = false;
            $widgetRecord->save();

            $widget->afterDelete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteWidget' event
        $this->trigger(self::EVENT_AFTER_DELETE_WIDGET, new WidgetEvent([
            'widget' => $widget,
        ]));

        return true;
    }

    /**
     * Reorders widgets.
     *
     * @param integer[] $widgetIds The widget IDs
     *
     * @return boolean Whether the widgets were reordered successfully
     * @throws \Exception if reasons
     */
    public function reorderWidgets($widgetIds)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($widgetIds as $widgetOrder => $widgetId) {
                $widgetRecord = $this->_getUserWidgetRecordById($widgetId);
                $widgetRecord->sortOrder = $widgetOrder + 1;
                $widgetRecord->save();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Changes the colspan of a widget.
     *
     * @param integer $widgetId
     * @param integer $colspan
     *
     * @return boolean
     */
    public function changeWidgetColspan($widgetId, $colspan)
    {
        $widgetRecord = $this->_getUserWidgetRecordById($widgetId);
        $widgetRecord->colspan = $colspan;
        $widgetRecord->save();

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
        $this->saveWidget($this->createWidget(RecentEntriesWidget::class));

        // Get Help widget
        if ($user->admin) {
            $this->saveWidget($this->createWidget(GetHelpWidget::class));
        }

        // Updates widget
        if ($user->can('performupdates')) {
            $this->saveWidget($this->createWidget(UpdatesWidget::class));
        }

        // Blog & Tonic feed widget
        $this->saveWidget($this->createWidget([
            'type' => FeedWidget::class,
            'url' => 'https://craftcms.com/news.rss',
            'title' => 'Craft News'
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

        if ($widgetId) {
            $widgetRecord = WidgetRecord::findOne([
                'id' => $widgetId,
                'userId' => $userId
            ]);

            if (!$widgetRecord) {
                $this->_noWidgetExists($widgetId);
            }
        } else {
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
     * @return void
     * @throws WidgetNotFoundException
     */
    private function _noWidgetExists($widgetId)
    {
        throw new WidgetNotFoundException("No widget exists with the ID '{$widgetId}'");
    }

    /**
     * Returns the widget records for the current user.
     *
     * @param string $indexBy
     *
     * @return WidgetInterface[]
     * @throws Exception if no user is logged-in
     */
    private function _getUserWidgets($indexBy = null)
    {
        $userId = Craft::$app->getUser()->getId();

        if (!$userId) {
            throw new Exception('No logged-in user');
        }

        $records = (new Query())
            ->select('id, type, colspan, settings')
            ->from('{{%widgets}}')
            ->where(['userId' => $userId, 'enabled' => 1])
            ->orderBy('sortOrder')
            ->all();

        $widgets = [];

        foreach ($records as $record) {
            $widget = $this->createWidget($record);

            if ($indexBy === null) {
                $widgets[] = $widget;
            } else {
                $widgets[$widget->$indexBy] = $widget;
            }
        }

        return $widgets;
    }
}
