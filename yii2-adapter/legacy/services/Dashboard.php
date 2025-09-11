<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\WidgetEvent;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Dashboard\Events\WidgetDeleted;
use CraftCms\Cms\Dashboard\Events\WidgetDeleting;
use CraftCms\Cms\Dashboard\Events\WidgetSaved;
use CraftCms\Cms\Dashboard\Events\WidgetSaving;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;

/**
 * Dashboard service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getDashboard()|`Craft::$app->getDashboard()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use `app(\CraftCms\Cms\Dashboard\Dashboard::class)` instead.
 */
class Dashboard extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering Dashboard widget types.
     *
     * Dashboard widgets must implement [[WidgetInterface]]. [[Widget]] provides a base implementation.
     *
     * See [Widget Types](https://craftcms.com/docs/5.x/extend/widget-types.html) for documentation on creating Dashboard widgets.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Dashboard;
     * use yii\base\Event;
     *
     * Event::on(Dashboard::class,
     *     Dashboard::EVENT_REGISTER_WIDGET_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyWidgetType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_WIDGET_TYPES = 'registerWidgetTypes';

    /**
     * @event WidgetEvent The event that is triggered before a widget is saved.
     */
    public const EVENT_BEFORE_SAVE_WIDGET = 'beforeSaveWidget';

    /**
     * @event WidgetEvent The event that is triggered after a widget is saved.
     */
    public const EVENT_AFTER_SAVE_WIDGET = 'afterSaveWidget';

    /**
     * @event WidgetEvent The event that is triggered before a widget is deleted.
     */
    public const EVENT_BEFORE_DELETE_WIDGET = 'beforeDeleteWidget';

    /**
     * @event WidgetEvent The event that is triggered after a widget is deleted.
     */
    public const EVENT_AFTER_DELETE_WIDGET = 'afterDeleteWidget';

    /**
     * Returns all available widget type classes.
     *
     * @return string[]
     * @phpstan-return class-string<WidgetInterface>[]
     */
    public function getAllWidgetTypes(): array
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->getAllWidgetTypes()->all();
    }

    /**
     * Creates a widget with a given config.
     *
     * @template T of WidgetInterface
     * @param class-string<T>|array $config The widget’s class name, or its config, with a `type` value and optionally a `settings` value.
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>,id?:int,dateCreated?:DateTime,dateUpdated?:DateTime,colspan?:int,settings?:array|string} $config
     * @return T
     */
    public function createWidget(mixed $config): WidgetInterface
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->createWidget($config);
    }

    /**
     * Returns the dashboard widgets for the current user.
     *
     * @return WidgetInterface[] The widgets
     */
    public function getAllWidgets(): array
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->getAllWidgets()->all();
    }

    /**
     * Returns whether the current user has a widget of the given type.
     *
     * @param class-string<WidgetInterface> $type The widget type
     *
     * @return bool Whether the current user has a widget of the given type
     */
    public function doesUserHaveWidget(string $type): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->doesUserHaveWidget($type);
    }

    /**
     * Returns a widget by its ID.
     *
     * @param int $id The widget’s ID
     *
     * @return WidgetInterface|null The widget, or null if it doesn’t exist
     */
    public function getWidgetById(int $id): ?WidgetInterface
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->getWidgetById($id);
    }

    /**
     * Saves a widget for the current user.
     *
     * @param WidgetInterface $widget The widget to be saved
     * @param bool $runValidation Whether the widget should be validated
     *
     * @return bool Whether the widget was saved successfully
     * @throws Throwable if reasons
     */
    public function saveWidget(WidgetInterface $widget, bool $runValidation = true): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->saveWidget($widget, $runValidation);
    }

    /**
     * Deletes a widget by its ID.
     *
     * @param int $widgetId The widget’s ID
     * @return bool Whether the widget was deleted successfully
     */
    public function deleteWidgetById(int $widgetId): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->deleteWidgetById($widgetId);
    }

    /**
     * Deletes a widget.
     *
     * @param WidgetInterface $widget The widget to be deleted
     *
     * @return bool Whether the widget was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteWidget(WidgetInterface $widget): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->deleteWidget($widget);
    }

    /**
     * Reorders widgets.
     *
     * @param int[] $widgetIds The widget IDs
     * @return bool Whether the widgets were reordered successfully
     * @throws Throwable if reasons
     */
    public function reorderWidgets(array $widgetIds): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->reorderWidgets($widgetIds);
    }

    /**
     * Changes the colspan of a widget.
     *
     * @param int $widgetId
     * @param int $colspan
     * @return bool
     */
    public function changeWidgetColspan(int $widgetId, int $colspan): bool
    {
        return app(\CraftCms\Cms\Dashboard\Dashboard::class)->changeWidgetColspan($widgetId, $colspan);
    }

    public static function registerEvents(): void
    {
        // Fire a 'registerWidgetTypes' event
        Event::listen(RegisterWidgetTypes::class, function(RegisterWidgetTypes $event) {
            $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->types->all()]);
            Craft::$app->getDashboard()->trigger(self::EVENT_REGISTER_WIDGET_TYPES, $yiiEvent);

            /** @var array<class-string<WidgetInterface>> $types */
            $types = $yiiEvent->types;

            $event->types = Collection::make($types);
        });

        // Fire a 'beforeSaveWidget' event
        Event::listen(WidgetSaving::class, function(WidgetSaving $event) {
            Craft::$app->getDashboard()->trigger(self::EVENT_BEFORE_SAVE_WIDGET, $yiiEvent = new WidgetEvent([
                'widget' => $event->widget,
                'isNew' => $event->isNew,
            ]));

            $event->widget = $yiiEvent->widget;
        });

        // Fire a 'afterSaveWidget' event
        Event::listen(WidgetSaved::class, function(WidgetSaved $event) {
            Craft::$app->getDashboard()->trigger(self::EVENT_AFTER_SAVE_WIDGET, $yiiEvent = new WidgetEvent([
                'widget' => $event->widget,
                'isNew' => $event->isNew,
            ]));
        });

        // Fire a 'beforeDeleteWidget' event
        Event::listen(WidgetDeleting::class, function(WidgetDeleting $event) {
            Craft::$app->getDashboard()->trigger(self::EVENT_BEFORE_DELETE_WIDGET, new WidgetEvent([
                'widget' => $event->widget,
            ]));
        });

        // Fire an 'afterDeleteWidget' event
        Event::listen(WidgetDeleted::class, function(WidgetDeleted $event) {
            Craft::$app->getDashboard()->trigger(self::EVENT_AFTER_DELETE_WIDGET, new WidgetEvent([
                'widget' => $event->widget,
            ]));
        });
    }
}
