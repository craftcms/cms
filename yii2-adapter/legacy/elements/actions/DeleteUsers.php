<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Actions\DeleteUsers} instead.
     */
    class DeleteUsers extends \CraftCms\Cms\User\Actions\DeleteUsers
    {
    }
}

class_alias(\CraftCms\Cms\User\Actions\DeleteUsers::class, DeleteUsers::class);
