<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use DateTime;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ExpirableElementInterface defines the common interface to be implemented by element classes that can expire.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Contracts\ExpirableElementInterface} instead.
     */
    interface ExpirableElementInterface
    {
        /**
         * Returns the element’s expiration date/time.
         *
         * @return DateTime|null
         */
        public function getExpiryDate(): ?DateTime;
    }
}

class_alias(\CraftCms\Cms\Element\Contracts\ExpirableElementInterface::class, ExpirableElementInterface::class);
