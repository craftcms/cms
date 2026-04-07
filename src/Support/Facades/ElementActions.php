<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array availableActions(string $elementType, string $sourceKey, ElementQueryInterface $elementQuery)
 * @method static ElementActionInterface createAction(mixed $action, string $elementType)
 * @method static array serializeActions(iterable $actions)
 * @method static ElementActionInterface|null resolveAction(iterable $actions, string $actionClass)
 * @method static array{valid:bool,success:bool,message:?string} invoke(\CraftCms\Cms\Element\Contracts\ElementActionInterface $action, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query)
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
