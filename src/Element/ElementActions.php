<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Actions\ClientAction;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementActionPerformed;
use CraftCms\Cms\Element\Events\ElementActionPerforming;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Singleton;

/**
 * @since 6.0.0
 */
#[Singleton]
class ElementActions
{
    /** @var array<class-string<ElementActionInterface>, string> */
    private const array SELECTION_ATTRIBUTES = [
        Duplicate::class => 'duplicatable',
        Delete::class => 'deletable',
    ];

    /**
     * The element type's actions for a source: element actions, created and
     * ready to perform, and {@see ClientAction}s, as they were defined.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return list<ElementActionInterface|ClientAction>
     */
    public function availableActions(string $elementType, string $sourceKey, ElementQueryInterface $elementQuery): array
    {
        $actions = $this->withCurrentQuery($elementQuery, fn (): array => $elementType::actions($sourceKey));

        foreach ($actions as $index => $action) {
            if ($action instanceof ClientAction) {
                if ($elementQuery->trashed) {
                    unset($actions[$index]);
                }

                continue;
            }

            $actions[$index] = $this->createAction($action, $elementType);

            if ($elementQuery->trashed) {
                if ($actions[$index] instanceof DeleteActionInterface && $actions[$index]->canHardDelete()) {
                    $actions[$index]->setHardDelete();
                } elseif (! $actions[$index] instanceof Restore) {
                    unset($actions[$index]);
                }
            } elseif ($actions[$index] instanceof Restore) {
                unset($actions[$index]);
            }
        }

        $actions = array_values($actions);

        if ($elementQuery->trashed) {
            usort($actions, fn (ElementActionInterface|ClientAction $a, ElementActionInterface|ClientAction $b) => match (true) {
                $a instanceof Restore => -1,
                $b instanceof Restore => 1,
                default => 0,
            });
        }

        return $actions;
    }

    /**
     * Runs `$callback` with `$elementQuery` as the current element index's
     * query, as element types read it while defining their actions (Show in
     * folder only applies to a subfolder search, say), then puts back whatever
     * was current before.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withCurrentQuery(ElementQueryInterface $elementQuery, callable $callback): mixed
    {
        $currentIndex = app(CurrentElementIndex::class);
        $wasActive = $currentIndex->isActive();
        $previousQuery = $currentIndex->query();

        $currentIndex->activate($elementQuery);

        try {
            return $callback();
        } finally {
            if ($wasActive) {
                $currentIndex->activate($previousQuery);
            } else {
                $currentIndex->reset();
            }
        }
    }

    /**
     * @param  ElementActionInterface|class-string<ElementActionInterface>|array{type:class-string<ElementActionInterface>, ...}  $action
     * @param  class-string<ElementInterface>  $elementType
     */
    public function createAction(mixed $action, string $elementType): ElementActionInterface
    {
        if ($action instanceof ElementActionInterface) {
            $action->setElementType($elementType);

            return $action;
        }

        if (is_string($action)) {
            $action = ['type' => $action];
        }

        $action['elementType'] = $elementType;

        return ComponentHelper::createComponent($action, ElementActionInterface::class);
    }

    /**
     * @param  iterable<ElementActionInterface>  $actions
     */
    /**
     * @param  iterable<array-key,mixed>  $actions
     * @return array<array-key,mixed>
     */
    public function serializeActions(iterable $actions): array
    {
        $data = [];

        // Client actions have no legacy trigger to serialize
        foreach ($actions as $action) {
            if ($action instanceof ElementActionInterface) {
                $data[] = ElementHelper::actionConfig($action);
            }
        }

        return $data;
    }

    /**
     * Serializes element actions as primitive "action item" descriptors for the
     * Inertia CP, so the bulk-actions bar renders them through the shared
     * `craft-action-item` component / `runAction()` primitives — reusing the
     * built-in request, confirm dialog, loading spinner, and feedback instead of
     * bespoke client code.
     *
     * The descriptor's body carries the action class + its server-baked settings
     * (so variants sharing a class string, e.g. Delete vs. Delete-with-descendants,
     * are reconstructed). The client merges in the live selection (`elementIds`)
     * and index context (`elementType`, `source`, `context`) before performing.
     *
     * Client actions serialize as `event` actions instead: choosing one
     * dispatches its window event, with the selection in its detail.
     *
     * @param  iterable<ElementActionInterface|ClientAction>  $actions
     * @return list<array<string, mixed>>
     */
    public function serializeActionItems(iterable $actions): array
    {
        $url = Url::actionUrl('element-indexes/perform-action');
        $items = [];

        foreach ($actions as $action) {
            if ($action instanceof ClientAction) {
                $items[] = $action->toItem();

                continue;
            }

            $destructive = $action::isDestructive();

            $item = [
                // Stable identifier (the action class) so the client can single
                // out specific actions (e.g. render Set Status as its own button).
                'key' => $action::class,
                'label' => $action->getTriggerLabel(),
                'destructive' => $destructive,
                ...(isset(self::SELECTION_ATTRIBUTES[$action::class])
                    ? ['selectionAttribute' => self::SELECTION_ATTRIBUTES[$action::class]]
                    : []),
            ];

            if ($destructive) {
                $item['variant'] = 'danger';
            }

            if (! $action::supportsBulk()) {
                $item['bulk'] = false;
            }

            $item['action'] = [
                'type' => $action::isDownload() ? 'download' : 'http',
                'method' => 'POST',
                'url' => $url,
                'body' => ['elementAction' => $action::class] + $action->getSettings(),
            ];

            if (($confirm = $action->getConfirmationMessage()) !== null) {
                $item['action']['confirm'] = $confirm;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  iterable<ElementActionInterface|ClientAction>  $actions
     */
    public function resolveAction(iterable $actions, string $actionClass): ?ElementActionInterface
    {
        foreach ($actions as $availableAction) {
            if ($availableAction instanceof ElementActionInterface && $availableAction::class === $actionClass) {
                return clone $availableAction;
            }
        }

        return null;
    }

    /**
     * @return array{valid:bool,success:bool,message:?string}
     */
    public function invoke(ElementActionInterface $action, ElementQueryInterface $query): array
    {
        if (! $action->validate()) {
            return [
                'valid' => false,
                'success' => false,
                'message' => null,
            ];
        }

        event($beforeEvent = new ElementActionPerforming(
            action: $action,
            query: $query,
        ));

        if (! $beforeEvent->isValid) {
            return [
                'valid' => true,
                'success' => false,
                'message' => $beforeEvent->message,
            ];
        }

        $success = $action->performAction($query);
        $message = $action->getMessage();

        if ($success) {
            event(new ElementActionPerformed(
                action: $action,
                query: $query,
                message: $message,
            ));
        }

        return [
            'valid' => true,
            'success' => $success,
            'message' => $message,
        ];
    }
}
