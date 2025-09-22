<?php

namespace craft\helpers;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Typecast Helper
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Typecast} instead.
     */
    final class Typecast
    {
    }
}

class_alias(\CraftCms\Cms\Support\Typecast::class, Typecast::class);
