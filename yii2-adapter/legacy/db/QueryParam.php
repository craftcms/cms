<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Class QueryParam
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Database\QueryParam} instead.
     */
    final class QueryParam
    {
    }
}

class_alias(\CraftCms\Cms\Database\QueryParam::class, QueryParam::class);
