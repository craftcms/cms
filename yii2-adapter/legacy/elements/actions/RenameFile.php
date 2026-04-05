<?php

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Actions\RenameFile} instead.
     */
    class RenameFile extends \CraftCms\Cms\Asset\Actions\RenameFile
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Actions\RenameFile::class, RenameFile::class);
