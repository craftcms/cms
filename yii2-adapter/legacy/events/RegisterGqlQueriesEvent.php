<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterGqlQueryEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Gql\Events\RegisterGqlQueries} instead.
 */
class RegisterGqlQueriesEvent extends Event
{
    /**
     * @var array[] List of GraphQL query definitions
     */
    public array $queries = [];
}
