<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface[] availableActions(class-string<\craft\base\ElementInterface> $elementType, string $sourceKey, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface createAction(\CraftCms\Cms\Element\Contracts\ElementActionInterface|class-string<\CraftCms\Cms\Element\Contracts\ElementActionInterface>|array $action, class-string<\craft\base\ElementInterface> $elementType)
 * @method static array serializeActions(iterable<\CraftCms\Cms\Element\Contracts\ElementActionInterface> $actions)
 * @method static \CraftCms\Cms\Element\Contracts\ElementActionInterface|null resolveAction(iterable<\CraftCms\Cms\Element\Contracts\ElementActionInterface> $actions, string $actionClass)
 * @method static array invoke(\CraftCms\Cms\Element\Contracts\ElementActionInterface $action, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query)
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
