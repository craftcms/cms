<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array availableActions(string $elementType, string $sourceKey, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface createAction(mixed $action, string $elementType)
 * @method static array serializeActions(iterable $actions)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface|null resolveAction(iterable $actions, string $actionClass)
 * @method static array{valid:bool,success:bool,message:?string} invoke(\CraftCms\Cms\Element\Contracts\ElementActionInterface $action, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query)
 *
 * @see \CraftCms\Cms\Element\Actions\ElementActions
 */
class ElementActions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\Actions\ElementActions::class;
    }
}
