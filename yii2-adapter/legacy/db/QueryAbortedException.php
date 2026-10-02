<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

/**
 * Class Exception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException} instead.
 */
class QueryAbortedException extends \CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException
{
    /**
     * @return string The user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Query Aborted Exception';
    }
}
