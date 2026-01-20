<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Dashboard\Events\WidgetDeleted;
use CraftCms\Cms\Dashboard\Events\WidgetDeleting;
use CraftCms\Cms\Dashboard\Events\WidgetSaved;
use CraftCms\Cms\Dashboard\Events\WidgetSaving;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport as CraftSupportWidget;
use CraftCms\Cms\Dashboard\Widgets\Feed as FeedWidget;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers as NewUsersWidget;
use CraftCms\Cms\Dashboard\Widgets\QuickPost as QuickPostWidget;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries as RecentEntriesWidget;
use CraftCms\Cms\Dashboard\Widgets\Updates as UpdatesWidget;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\User\Models\User;
use DateTimeInterface;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

#[Singleton]
final readonly class Dashboard
{
    /**
     * @return Collection<class-string<WidgetInterface>>
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
     * @param  class-string<T>|array{type: class-string<T>, id?: int, colspan?: int, settings?: array<string, mixed>, dateCreated?: DateTimeInterface, dateUpdated?: DateTimeInterface}  $config  The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @return T
     */
    public function createWidget(string|array $config): WidgetInterface
    {
        if (is_string($config)) {
            return app($config);
        }

        return Widget::fromConfig($config);
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
     * @return WidgetInterface The widget
     *
     * @throws ModelNotFoundException if it doesn't exist
     */
    public function getWidgetById(int $id): WidgetInterface
    {
        $result = Models\Widget::query()
            ->where('id', $id)
            ->where('userId', Auth::user()->getAuthIdentifier())
            ->firstOrFail();

        return Widget::fromConfig($result);
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

        if (! $widget->beforeSave($isNewWidget)) {
            return false;
        }

        /**
         * Legacy widgets run validation through the ->validate() method.
         */
        if ($runValidation && ! $widget->validate()) {
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

            $widget->afterSave($isNewWidget);

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

        if (! $widget->beforeDelete()) {
            return false;
        }

        DB::beginTransaction();

        try {
            $widgetRecord = $this->getUserWidgetModelById($widget->id);
            $widgetRecord->delete();
            $widget->afterDelete();
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
        $cases = '';

        foreach ($widgetIds as $index => $id) {
            $cases .= " WHEN {$id} THEN {$index}";
        }

        DB::table((new Models\Widget)->getTable())
            ->whereIn('id', $widgetIds)
            ->update([
                'sortOrder' => DB::raw("CASE id {$cases} END"),
            ]);

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
        $user = Auth::user();

        // Recent Entries widget
        $this->saveWidget($this->createWidget(RecentEntriesWidget::class));

        // Craft Support widget
        if ($user->isAdmin()) {
            $this->saveWidget($this->createWidget(CraftSupportWidget::class));
        }

        // Updates widget
        if ($user->can('performUpdates')) {
            $this->saveWidget($this->createWidget(UpdatesWidget::class));
        }

        // Craft News feed widget
        $this->saveWidget($this->createWidget([
            'type' => FeedWidget::class,
            'settings' => [
                'url' => 'https://craftcms.com/news.rss',
                'title' => 'Craft News',
            ],
        ]));

        User::where('id', $user->id)->update([
            'hasDashboard' => true,
        ]);

        $user->hasDashboard = true;
    }

    private function getUserWidgetModelById(?int $widgetId = null): Models\Widget
    {
        $userId = Auth::user()->getAuthIdentifier();

        if ($widgetId !== null) {
            return Models\Widget::query()
                ->where('id', $widgetId)
                ->where('userId', $userId)
                ->firstOrFail();
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
        /** @var User $user */
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
            ->map(fn (Models\Widget $widget) => Widget::fromConfig($widget));
    }
}
