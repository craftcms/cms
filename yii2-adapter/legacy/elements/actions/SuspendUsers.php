<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Actions\SuspendUsers} instead.
     */
    class SuspendUsers extends \CraftCms\Cms\User\Actions\SuspendUsers
    {
    }
}

class_alias(\CraftCms\Cms\User\Actions\SuspendUsers::class, SuspendUsers::class);
