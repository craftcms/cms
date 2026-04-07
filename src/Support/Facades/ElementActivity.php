<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<int, \CraftCms\Cms\Element\Data\ElementActivity> getRecentActivity(\craft\base\ElementInterface $element, int|null $excludeUserId = null)
 * @method static void trackActivity(\craft\base\ElementInterface $element, \CraftCms\Cms\Element\Enums\ElementActivityType $type, \CraftCms\Cms\User\Elements\User|null $user = null)
 *
 * @see \CraftCms\Cms\Element\ElementActivity
 */
class ElementActivity extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\ElementActivity::class;
    }
}
