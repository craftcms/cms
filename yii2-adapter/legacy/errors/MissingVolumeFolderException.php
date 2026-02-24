<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException} instead.
     */
    class MissingVolumeFolderException
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException::class, MissingVolumeFolderException::class);
