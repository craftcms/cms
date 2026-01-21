<?php

namespace craft\elements;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Address element class
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Elements\Address} instead.
     */
    class Address
    {
    }
}

class_alias(\CraftCms\Cms\Address\Elements\Address::class, Address::class);
