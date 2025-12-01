<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Describable defines the common interface to be implemented by components that
     * have description within their chips.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.8.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Contracts\Describable} instead.
     */
    interface Describable
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\Describable::class, Describable::class);
