<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ContentBlockQuery represents a SELECT SQL statement for content blocks in a way that is independent of DBMS.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.8.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\ContentBlockQuery} instead.
     */
    class ContentBlockQuery extends ElementQuery implements NestedElementQueryInterface
    {
        use QueriesNestedElements;

        public function getFieldIdColumn(): string
        {
            return '';
        }

        public function getPrimaryOwnerIdColumn(): string
        {
            return '';
        }
    }
}
