<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\elements\actions\Delete;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View as ViewAction;
use craft\events\RegisterElementActionsEvent;
use Illuminate\Support\Collection;
use yii\base\Event;

use function CraftCms\Cms\t;

/**
 * HasActions provides element action management functionality.
 *
 * This trait contains methods for defining and retrieving available bulk actions
 * for elements in the control panel index view.
 *
 * @internal
 */
trait HasActions
{
    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available bulk actions for the element type.
     */
    public const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * {@inheritdoc}
     */
    public static function actions(string $source): array
    {
        $actions = Collection::make(static::defineActions($source));

        $hasActionType = fn (string $type) => $actions->contains(
            fn ($action) => (
                $action === $type ||
                $action instanceof $type ||
                is_subclass_of($action, $type) ||
                (
                    is_array($action) &&
                    isset($action['type']) &&
                    ($action['type'] === $type || is_subclass_of($action['type'], $type))
                )
            ),
        );

        // Prepend Duplicate?
        if (! $hasActionType(Duplicate::class)) {
            $actions->prepend(Duplicate::class);
        }

        // Prepend Edit?
        if (! $hasActionType(Edit::class)) {
            $actions->prepend([
                'type' => Edit::class,
                'label' => mb_ucfirst(t('Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ]);
        }

        // Prepend View?
        if (static::hasUris() && ! $hasActionType(ViewAction::class)) {
            $actions->prepend([
                'type' => ViewAction::class,
                'label' => mb_ucfirst(t('View {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ]);
        }

        // Prepend Set Status?
        if (static::includeSetStatusAction() && ! $hasActionType(SetStatus::class)) {
            $actions->prepend(SetStatus::class);
        }

        // Append Delete?
        if (! $hasActionType(DeleteActionInterface::class)) {
            $actions->push(Delete::class);
        }

        $actions = $actions->all();

        // Fire a 'registerActions' event
        if (Event::hasHandlers(static::class, static::EVENT_REGISTER_ACTIONS)) {
            $event = new RegisterElementActionsEvent([
                'source' => $source,
                'actions' => $actions,
            ]);
            Event::trigger(static::class, static::EVENT_REGISTER_ACTIONS, $event);

            return $event->actions;
        }

        return $actions;
    }

    /**
     * Returns whether the Set Status action should be included in [[actions()]] automatically.
     *
     * @since 4.3.2
     */
    protected static function includeSetStatusAction(): bool
    {
        return false;
    }

    /**
     * Defines the available bulk element actions for a given source.
     *
     * @param  string  $source  The selected source's key, if any.
     * @return array The available bulk element actions.
     *
     * @see actions()
     */
    protected static function defineActions(string $source): array
    {
        return [];
    }
}
