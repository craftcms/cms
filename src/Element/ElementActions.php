<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Events\AfterPerformAction;
use CraftCms\Cms\Element\Events\BeforePerformAction;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class ElementActions
{
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
     * @param  ElementActionInterface|class-string<ElementActionInterface>|array{type:class-string<ElementActionInterface>}  $action
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
    public function serializeActions(iterable $actions): array
    {
        $data = [];

        foreach ($actions as $action) {
            $data[] = ElementHelper::actionConfig($action);
        }

        return $data;
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

        event($beforeEvent = new BeforePerformAction(
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
            event(new AfterPerformAction(
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
