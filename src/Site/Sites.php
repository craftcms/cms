<?php

namespace CraftCms\Cms\Site;

use craft\models\Site;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
final class Sites
{
    /**
     * Returns sites by a group ID.
     *
     * @return Site[]
     */
    public function getSitesByGroupId(int $groupId, ?bool $withDisabled = null): array
    {
        return \Craft::$app->getSites()->getSitesByGroupId($groupId, $withDisabled);
    }
}
