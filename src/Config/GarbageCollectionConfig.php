<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Config as ConfigHelper;
use InvalidArgumentException;

/**
 * Settings for how long garbage collection keeps old data.
 *
 * @since 6.0.0
 */
class GarbageCollectionConfig extends BaseConfig
{
    /**
     * The maximum age of activity events before garbage collection deletes them.
     *
     * Set to `0` to retain activity indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt Unlimited
     */
    public mixed $activityRetentionDuration = 0;

    /**
     * The amount of time to wait before Craft purges pending users from the system that have not activated.
     *
     * Any content assigned to a pending user will be deleted as well when the given time interval passes.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: tip
     * Users will only be purged when [garbage collection](https://craftcms.com/docs/5.x/system/gc.html) is run.
     * :::
     */
    public mixed $purgePendingUsersDuration = 0;

    /**
     * The amount of time to wait before Craft purges unpublished drafts that were never updated with content.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 30 days
     */
    public mixed $purgeUnsavedDraftsDuration = 2592000;

    /**
     * The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection.
     *
     * Set to `0` if you don’t ever want to delete soft-deleted items.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 30 days
     */
    public mixed $softDeleteDuration = 2592000;

    public function activityRetentionDuration(mixed $value): self
    {
        $duration = ConfigHelper::durationInSeconds($value);

        if ($duration < 0) {
            throw new InvalidArgumentException('Activity retention duration must be zero or greater.');
        }

        $this->activityRetentionDuration = $duration;

        return $this;
    }

    public function purgePendingUsersDuration(mixed $value): self
    {
        $this->purgePendingUsersDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function purgeUnsavedDraftsDuration(mixed $value): self
    {
        $this->purgeUnsavedDraftsDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function softDeleteDuration(mixed $value): self
    {
        $this->softDeleteDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }
}
