<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementActionPerformed;
use CraftCms\Cms\Element\Events\ElementActionPerforming;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Actions\MoveToSection;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class ElementActions
{
    /**
     * Actions whose interaction (a status picker, a section picker, …) hasn't
     * been ported to the Inertia CP yet. They're serialized for the bulk-actions
     * bar but flagged `disabled` until each gets a native Vue control, rather
     * than performing silently with a default. Referenced by class string to
     * avoid coupling this Element-layer service to Entry-layer actions.
     *
     * @var list<class-string<ElementActionInterface>>
     */
    private const array INTERACTIVE_ACTIONS = [
        MoveToSection::class,
    ];

    /**
     * Actions that run entirely client-side, serialized as `event` actions:
     * clicking one dispatches the mapped window event (with the live selection
     * in its detail) instead of POSTing to the perform endpoint. Copy mirrors
     * Craft 5, where it stores the selection in localStorage for paste targets.
     *
     * @var array<class-string<ElementActionInterface>, string>
     */
    private const array CLIENT_EVENT_ACTIONS = [
        Actions\Copy::class => 'craft:copy-elements',
    ];

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ElementActionInterface[]
     */
    public function availableActions(string $elementType, string $sourceKey, ElementQueryInterface $elementQuery): array
    {
        $actions = $elementType::actions($sourceKey);

        foreach ($actions as $index => $action) {
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
            usort($actions, fn (ElementActionInterface $a, ElementActionInterface $b) => match (true) {
                $a instanceof Restore => -1,
                $b instanceof Restore => 1,
                default => 0,
            });
        }

        return $actions;
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

        foreach ($actions as $action) {
            $data[] = ElementHelper::actionConfig($action);
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
     * @param  iterable<ElementActionInterface>  $actions
     * @return list<array<string, mixed>>
     */
    public function serializeActionItems(iterable $actions): array
    {
        $url = Url::actionUrl('element-indexes/perform-action');
        $items = [];

        foreach ($actions as $action) {
            $destructive = $action::isDestructive();

            $item = [
                // Stable identifier (the action class) so the client can single
                // out specific actions (e.g. render Set Status as its own button).
                'key' => $action::class,
                'label' => $action->getTriggerLabel(),
                'destructive' => $destructive,
            ];

            if ($destructive) {
                $item['variant'] = 'danger';
            }

            if (! $action::supportsBulk()) {
                $item['bulk'] = false;
            }

            // Interactive actions still need a native Vue picker before they can
            // submit; surface them disabled until ported (see INTERACTIVE_ACTIONS).
            if (in_array($action::class, self::INTERACTIVE_ACTIONS, true)) {
                $item['disabled'] = true;
                $items[] = $item;

                continue;
            }

            if (isset(self::CLIENT_EVENT_ACTIONS[$action::class])) {
                $item['action'] = [
                    'type' => 'event',
                    'name' => self::CLIENT_EVENT_ACTIONS[$action::class],
                ];
                $items[] = $item;

                continue;
            }

            // NOTE: download actions aren't mapped yet — the `download` primitive
            // is a GET link and can't POST the selection/body. No entry actions
            // download today; revisit when bulk asset downloads land.
            $item['action'] = [
                'type' => 'http',
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
     * @param  iterable<ElementActionInterface>  $actions
     */
    public function resolveAction(iterable $actions, string $actionClass): ?ElementActionInterface
    {
        foreach ($actions as $availableAction) {
            if ($availableAction::class === $actionClass) {
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
