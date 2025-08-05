<?php

namespace CraftCms\Cms\Dashboard;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Dashboard\Events\WidgetDeleted;
use CraftCms\Cms\Dashboard\Events\WidgetDeleting;
use CraftCms\Cms\Dashboard\Events\WidgetSaved;
use CraftCms\Cms\Dashboard\Events\WidgetSaving;
use CraftCms\Cms\Dashboard\Exceptions\WidgetNotFoundException;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport as CraftSupportWidget;
use CraftCms\Cms\Dashboard\Widgets\Feed as FeedWidget;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers as NewUsersWidget;
use CraftCms\Cms\Dashboard\Widgets\QuickPost as QuickPostWidget;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries as RecentEntriesWidget;
use CraftCms\Cms\Dashboard\Widgets\Updates as UpdatesWidget;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use yii\base\Exception;

#[Singleton]
class Dashboard
{
    /**
     * @return Collection<int, class-string<WidgetInterface>>
     */
    public function getAllWidgetTypes(): Collection
    {
        /** @var Collection<int, class-string<WidgetInterface>> $widgetTypes */
        $widgetTypes = Collection::make([
            FeedWidget::class,
            CraftSupportWidget::class,
            NewUsersWidget::class,
            QuickPostWidget::class,
            RecentEntriesWidget::class,
            MyDrafts::class,
            UpdatesWidget::class,
        ]);

        if (Event::hasListeners(RegisterWidgetTypes::class)) {
            Event::dispatch($event = new RegisterWidgetTypes($widgetTypes));

            return $event->types;
        }

        return $widgetTypes;
    }

    /**
     * Creates a widget with a given config.
     *
     * @template T of WidgetInterface
     *
     * @param class-string<T>|array{
     *     type:class-string<T>,
     *     id?:int,
     *     dateCreated?:\DateTimeInterface,
     *     dateUpdated?:\DateTimeInterface,
     *     colspan?:int,
     *     settings?:array|string
     * } $config  The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @return T
     */
    public function createWidget(string|array $config): WidgetInterface
    {
        if (is_string($config)) {
            return new $config;
        }

        return (new Models\Widget($config))->toWidget();
    }

    /**
     * Returns the dashboard widgets for the current user.
     *
     * @return Collection<WidgetInterface> The widgets
     */
    public function getAllWidgets(): Collection
    {
        $widgets = $this->getUserWidgets();

        // If there are no widgets, this is the first time they've hit the dashboard.
        if ($widgets === false) {
            // Add the defaults and try again
            $this->addDefaultUserWidgets();
            $widgets = $this->getUserWidgets();
        }

        return $widgets;
    }

    /**
     * Returns whether the current user has a widget of the given type.
     *
     * @param  class-string<WidgetInterface>  $type  The widget type
     * @return bool Whether the current user has a widget of the given type
     */
    public function doesUserHaveWidget(string $type): bool
    {
        return Models\Widget::query()
            ->where('userId', Auth::user()->getAuthIdentifier())
            ->where('type', $type)
            ->exists();
    }

    /**
     * Returns a widget by its ID.
     *
     * @param  int  $id  The widget’s ID
     * @return WidgetInterface The widget, or null if it doesn’t exist
     */
    public function getWidgetById(int $id): WidgetInterface
    {
        $result = Models\Widget::query()
            ->where('id', $id)
            ->where('userId', Auth::user()->getAuthIdentifier())
            ->firstOrFail();

        return $result->toWidget();
    }

    /**
     * Saves a widget for the current user.
     *
     * @param  WidgetInterface  $widget  The widget to be saved
     * @param  bool  $runValidation  Whether the widget should be validated
     * @return bool Whether the widget was saved successfully
     *
     * @throws ValidationException if widget data is invalid
     */
    public function saveWidget(WidgetInterface $widget, bool $runValidation = true): bool
    {
        $isNewWidget = ! $widget->id;

        if (Event::hasListeners(WidgetSaving::class)) {
            Event::dispatch($event = new WidgetSaving($widget, $isNewWidget));

            if (! $event->isValid) {
                return false;
            }

            $widget = $event->widget;
        }

        /**
         * Legacy widgets run validation through the ->validate() method.
         */
        if ($runValidation && empty($widget::getSettingsRules()) && ! $widget->validate()) {
            Log::info('Widget not saved due to validation error.', ['widget' => $widget]);

            throw ValidationException::withMessages($widget->getFirstErrors());
        }

        DB::beginTransaction();

        try {
            $widgetModel = $this->getUserWidgetModelById($widget->id);

            $widgetModel->type = $widget::class;
            $widgetModel->settings = $widget->getSettings();

            if ($isNewWidget) {
                // Set the sortOrder
                $maxSortOrder = Models\Widget::query()
                    ->where('userId', Auth::user()->getAuthIdentifier())
                    ->max('sortOrder');

                $widgetModel->sortOrder = $maxSortOrder + 1;
            }

            $widgetModel->save();

            $widget->id = $widgetModel->id;

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        if (Event::hasListeners(WidgetSaved::class)) {
            Event::dispatch(new WidgetSaved($widget, $isNewWidget));
        }

        return true;
    }

    /**
     * Deletes a widget by its ID.
     *
     * @param  int  $widgetId  The widget’s ID
     * @return bool Whether the widget was deleted successfully
     */
    public function deleteWidgetById(int $widgetId): bool
    {
        return $this->deleteWidget($this->getWidgetById($widgetId));
    }

    /**
     * Deletes a widget.
     *
     * @param  WidgetInterface  $widget  The widget to be deleted
     * @return bool Whether the widget was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteWidget(WidgetInterface $widget): bool
    {
        if (Event::hasListeners(WidgetDeleting::class)) {
            $event = new WidgetDeleting($widget);
            Event::dispatch($event);

            if (! $event->isValid) {
                return false;
            }
        }

        DB::beginTransaction();

        try {
            $widgetRecord = $this->getUserWidgetModelById($widget->id);
            $widgetRecord->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        if (Event::hasListeners(WidgetDeleted::class)) {
            Event::dispatch(new WidgetDeleted($widget));
        }

        return true;
    }

    /**
     * Reorders widgets.
     *
     * @param  int[]  $widgetIds  The widget IDs
     * @return bool Whether the widgets were reordered successfully
     *
     * @throws Throwable if reasons
     */
    public function reorderWidgets(array $widgetIds): bool
    {
        DB::beginTransaction();

        try {
            $widgets = Models\Widget::query()
                ->where('userId', Auth::user()->getAuthIdentifier())
                ->whereIn('id', $widgetIds)
                ->get()
                ->keyBy('id');

            foreach ($widgetIds as $widgetOrder => $widgetId) {
                $widgetModel = $widgets[$widgetId];
                $widgetModel->sortOrder = $widgetOrder + 1;
                $widgetModel->save();
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Changes the colspan of a widget.
     */
    public function changeWidgetColspan(int $widgetId, int $colspan): bool
    {
        $this->getUserWidgetModelById($widgetId)->update([
            'colspan' => $colspan,
        ]);

        return true;
    }

    /**
     * Adds the default widgets to the logged-in user.
     */
    private function addDefaultUserWidgets(): void
    {
        /** @var \CraftCms\Cms\User\Models\User $user */
        $user = Auth::user();

        // Recent Entries widget
        $this->saveWidget($this->createWidget(RecentEntriesWidget::class));

        // Craft Support widget
        if ($user->admin) {
            $this->saveWidget($this->createWidget(CraftSupportWidget::class));
        }

        // Updates widget
        if ($user->can('performUpdates')) {
            $this->saveWidget($this->createWidget(UpdatesWidget::class));
        }

        // Craft News feed widget
        $this->saveWidget($this->createWidget([
            'type' => FeedWidget::class,
            'url' => 'https://craftcms.com/news.rss',
            'title' => 'Craft News',
        ]));

        $user->update([
            'hasDashboard' => true,
        ]);
    }

    private function getUserWidgetModelById(?int $widgetId = null): Models\Widget
    {
        $userId = Auth::user()->getAuthIdentifier();

        if ($widgetId !== null) {
            $widgetModel = Models\Widget::query()
                ->where('id', $widgetId)
                ->where('userId', $userId)
                ->first();

            throw_if(
                ! $widgetModel,
                WidgetNotFoundException::class,
                "No widget exists with the ID '$widgetId'"
            );

            return $widgetModel;
        }

        $widgetModel = new Models\Widget;
        $widgetModel->userId = $userId;

        return $widgetModel;
    }

    /**
     * Returns the widget records for the current user.
     *
     * @return Collection<WidgetInterface>|false
     *
     * @throws Exception if no user is logged-in
     */
    private function getUserWidgets(): Collection|false
    {
        /** @var \CraftCms\Cms\User\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            throw new Exception('No logged-in user');
        }

        if (! $user->hasDashboard) {
            return false;
        }

        return Models\Widget::query()
            ->where('userId', $user->id)
            ->orderBy('sortOrder')
            ->get()
            ->map(fn (Models\Widget $widget) => $widget->toWidget());
    }
}
