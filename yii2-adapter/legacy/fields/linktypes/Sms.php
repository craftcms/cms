<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

/** @phpstan-ignore-next-line **/
if (false) {
    /**
     * @since 5.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\LinkTypes\Sms} instead.
     */
    class Sms
    {
    }
}

class_alias(\CraftCms\Cms\Field\LinkTypes\Sms::class, Sms::class);
