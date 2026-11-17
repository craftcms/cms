<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\OperationTypes;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * An element action run from an element index.
 *
 * @since 6.0.0
 */
class ElementActionOperation implements ActivityOperationTypeInterface
{
    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    public static function for(ElementActionInterface $action, string $elementType, int $count): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: Str::uuid()->toString(),
            label: self::describe($action, $elementType, $count),
            data: [
                // Settings can change the action's label, as with Delete's hard setting
                'action' => Json::encode(['type' => $action::class, ...$action->getSettings()]),
                'elementType' => $elementType,
                'count' => $count,
            ],
        );
    }

    public static function label(string $id, array $snapshot): ?string
    {
        $elementType = $snapshot['elementType'] ?? null;

        if (! is_string($elementType) || ! is_a($elementType, ElementInterface::class, true)) {
            return null;
        }

        try {
            $action = app(ElementActions::class)->createAction(Json::decode((string) $snapshot['action']), $elementType);
        } catch (MissingComponentException|RuntimeException) {
            // The action's plugin may have been uninstalled
            return null;
        }

        return self::describe($action, $elementType, (int) $snapshot['count']);
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        return null;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function describe(ElementActionInterface $action, string $elementType, int $count): string
    {
        return t('{action} ({count, number} {type})', [
            'action' => $action->getTriggerLabel(),
            'count' => $count,
            'type' => $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName(),
        ]);
    }
}
