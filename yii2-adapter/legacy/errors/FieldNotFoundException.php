<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Class FieldNotFoundException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.4
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Exceptions\FieldNotFoundException} instead.
     */
    class FieldNotFoundException
    {
    }
}

class_alias(\CraftCms\Cms\Field\Exceptions\FieldNotFoundException::class, FieldNotFoundException::class);
