<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface[] availableActions(string $elementType, string $sourceKey, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface createAction(\CraftCms\Cms\Element\Contracts\ElementActionInterface|string|array<array-key, mixed> $action, string $elementType)
 * @method static array<array-key, mixed> serializeActions(iterable<array-key, mixed> $actions)
 * @method static array<array-key, mixed> serializeActionItems(iterable<array-key, mixed> $actions)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface|null resolveAction(iterable<array-key, mixed> $actions, string $actionClass)
 * @method static array<array-key, mixed> invoke(\CraftCms\Cms\Element\Contracts\ElementActionInterface $action, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query)
 *
 * @see \CraftCms\Cms\Element\ElementActions
 */
class ElementActions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\ElementActions::class;
    }
}
