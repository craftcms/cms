<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Widget;
use craft\base\WidgetInterface;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\errors\WidgetNotFoundException;
use craft\events\RegisterComponentTypesEvent;
use craft\events\WidgetEvent;
use craft\helpers\Component as ComponentHelper;
use craft\records\Widget as WidgetRecord;
use craft\widgets\CraftSupport as CraftSupportWidget;
use craft\widgets\Feed as FeedWidget;
use craft\widgets\MissingWidget;
use craft\widgets\NewUsers as NewUsersWidget;
use craft\widgets\QuickPost as QuickPostWidget;
use craft\widgets\RecentEntries as RecentEntriesWidget;
use craft\widgets\Updates as UpdatesWidget;
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
     * @event RegisterComponentTypesEvent The event that is triggered when registering Dashboard widget types.
     */
    const EVENT_REGISTER_WIDGET_TYPES = 'registerWidgetTypes';

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
     * @return string[]
     */
    public function getAllWidgetTypes(): array
    {
        $widgetTypes = [
            FeedWidget::class,
            CraftSupportWidget::class,
            NewUsersWidget::class,
            QuickPostWidget::class,
            RecentEntriesWidget::class,
            UpdatesWidget::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $widgetTypes
        ]);
        $this->trigger(self::EVENT_REGISTER_WIDGET_TYPES, $event);

        return $event->types;
    }

    /**
     * Creates a widget with a given config.
     *
     * @param mixed $config The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
     *
     * @return WidgetInterface
     */
    public function createWidget($config): WidgetInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            /** @var Widget $widget */
            $widget = ComponentHelper::createComponent($config, WidgetInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $widget = new MissingWidget($config);
        }

        return $widget;
    }

    /**
     * Returns the dashboard widgets for the current user.
     *
     * @return WidgetInterface[] The widgets
     */
    public function getAllWidgets(): array
    {
        $widgets = $this->_getUserWidgets();

        // If there are no widgets, this is the first time they've hit the dashboard.
        if (empty($widgets)) {
            // Add the defaults and try again
            $this->_addDefaultUserWidgets();
            $widgets = $this->_getUserWidgets();
        }

        return $widgets;
    }

    /**
     * Returns whether the current user has a widget of the given type.
     *
     * @param string $type The widget type
     *
     * @return bool Whether the current user has a widget of the given type
     */
    public function doesUserHaveWidget(string $type): bool
    {
        return WidgetRecord::find()
            ->where([
                'userId' => Craft::$app->getUser()->getIdentity()->id,
                'type' => $type,
                'enabled' => '1'
            ])
            ->exists();
    }

    /**
     * Returns a widget by its ID.
     *
     * @param int $id The widget’s ID
     *
     * @return WidgetInterface|null The widget, or null if it doesn’t exist
     */
    public function getWidgetById(int $id)
    {
        $result = $this->_createWidgetsQuery()
            ->where(['id' => $id, 'userId' => Craft::$app->getUser()->getIdentity()->id])
            ->one();

        if ($result) {
            return $this->createWidget($result);
        }

        return null;
    }

    /**
     * Saves a widget for the current user.
     *
     * @param WidgetInterface $widget        The widget to be saved
     * @param bool            $runValidation Whether the widget should be validated
     *
     * @return bool Whether the widget was saved successfully
     * @throws \Throwable if reasons
     */
    public function saveWidget(WidgetInterface $widget, bool $runValidation = true): bool
    {
        /** @var Widget $widget */
        $isNewWidget = $widget->getIsNew();

        // Fire a 'beforeSaveWidget' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_WIDGET)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_WIDGET, new WidgetEvent([
                'widget' => $widget,
                'isNew' => $isNewWidget,
            ]));
        }

        if (!$widget->beforeSave($isNewWidget)) {
            return false;
        }

        if ($runValidation && !$widget->validate()) {
            Craft::info('Widget not saved due to validation error.', __METHOD__);
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $widgetRecord = $this->_getUserWidgetRecordById($widget->id);

            $widgetRecord->type = get_class($widget);
            $widgetRecord->settings = $widget->getSettings();

            if ($isNewWidget) {
                // Enabled by default.
                $widgetRecord->enabled = true;

                // Set the sortOrder
                $maxSortOrder = (new Query())
                    ->from(['{{%widgets}}'])
                    ->where(['userId' => Craft::$app->getUser()->getIdentity()->id])
                    ->max('[[sortOrder]]');

                $widgetRecord->sortOrder = $maxSortOrder + 1;
            }

            $widgetRecord->save(false);

            // Now that we have a widget ID, save it on the model
            if ($isNewWidget) {
                $widget->id = $widgetRecord->id;
            }

            $widget->afterSave($isNewWidget);

            $transaction->commit();
        } catch (\Throwable $e) {
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
     * @param int $widgetId The widget’s ID
     *
     * @return bool Whether the widget was deleted successfully
     */
    public function deleteWidgetById(int $widgetId): bool
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
     * @return bool Whether the widget was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteWidget(WidgetInterface $widget): bool
    {
        /** @var Widget $widget */
        // Fire a 'beforeDeleteWidget' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_WIDGET)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_WIDGET, new WidgetEvent([
                'widget' => $widget,
            ]));
        }

        if (!$widget->beforeDelete()) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $widgetRecord = $this->_getUserWidgetRecordById($widget->id);
            $widgetRecord->enabled = false;
            $widgetRecord->save();

            $widget->afterDelete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteWidget' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_WIDGET)) {
            $this->trigger(self::EVENT_AFTER_DELETE_WIDGET, new WidgetEvent([
                'widget' => $widget,
            ]));
        }

        return true;
    }

    /**
     * Reorders widgets.
     *
     * @param int[] $widgetIds The widget IDs
     *
     * @return bool Whether the widgets were reordered successfully
     * @throws \Throwable if reasons
     */
    public function reorderWidgets(array $widgetIds): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($widgetIds as $widgetOrder => $widgetId) {
                $widgetRecord = $this->_getUserWidgetRecordById($widgetId);
                $widgetRecord->sortOrder = $widgetOrder + 1;
                $widgetRecord->save();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Changes the colspan of a widget.
     *
     * @param int $widgetId
     * @param int $colspan
     *
     * @return bool
     */
    public function changeWidgetColspan(int $widgetId, int $colspan): bool
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
            $this->saveWidget($this->createWidget(CraftSupportWidget::class));
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
     * @param int|null $widgetId
     *
     * @return WidgetRecord
     */
    private function _getUserWidgetRecordById(int $widgetId = null): WidgetRecord
    {
        $userId = Craft::$app->getUser()->getIdentity()->id;

        if ($widgetId !== null) {
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
     * @param int $widgetId
     *
     * @return void
     * @throws WidgetNotFoundException
     */
    private function _noWidgetExists(int $widgetId)
    {
        throw new WidgetNotFoundException("No widget exists with the ID '{$widgetId}'");
    }

    /**
     * Returns the widget records for the current user.
     *
     * @return WidgetInterface[]
     * @throws Exception if no user is logged-in
     */
    private function _getUserWidgets(): array
    {
        $userId = Craft::$app->getUser()->getId();

        if (!$userId) {
            throw new Exception('No logged-in user');
        }

        $results = $this->_createWidgetsQuery()
            ->where(['userId' => $userId, 'enabled' => '1'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        $widgets = [];
        foreach ($results as $result) {
            $widgets[] = $this->createWidget($result);
        }

        return $widgets;
    }

    /**
     * @return Query
     */
    private function _createWidgetsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'dateCreated',
                'dateUpdated',
                'colspan',
                'type',
                'settings',
            ])
            ->from(['{{%widgets}}']);
    }
}
