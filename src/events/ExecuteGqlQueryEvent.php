<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use GraphQL\Type\Schema;
use yii\base\Event;

/**
 * ExecuteGqlQueryEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.11
 */
class ExecuteGqlQueryEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Schema The GraphQL schema used for this query.
     */
    public $schemaDef;

    /**
     * @var string The GraphQL query being executed
     */
    public $query;

    /**
     * @var array The variables used for this query.
     */
    public $variables = [];

    /**
     * @var string|null The name of the operation to use if requestString contains multiple possible operations.
     */
    public $operationName;

    /**
     * @var mixed|null The context that is shared between all resolvers.
     */
    public $context;

    /**
     * @var mixed|null The root value to use when resolving the top-level object fields.
     */
    public $rootValue;

    /**
     * @var array|null The query result to be returned.
     */
    public $result;
}
