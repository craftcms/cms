<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Contracts\Identifiable} instead.
     */
    interface Identifiable
    {
        /**
         * Returns the ID of the component, which should be used as the value of hidden inputs.
         *
         * @return string|int|null
         */
        public function getId(): string|int|null;
    }
}
