<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;
use Graphql\Type\Schema;
use GraphQL\Language\AST\DocumentNode;

/**
 * Graphql before execute event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GraphqlBeforeExecuteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Schema The GraphQL type system to use when validating and executing a query.
     */
    public $schema;

    /**
     * @var string|DocumentNode A GraphQL language formatted string representing the requested operation.
     */
    public $source;

    /**
     * @var mixed The value provided as the first argument to resolver functions on the top level type
     * (e.g. the query object type).
     */
    public $rootValue;

    /**
     * @var mixed The value provided as the third argument to all resolvers.
     * Use this to pass current session, user data, etc
     */
    public $context;

    /**
     * @var array|null A mapping of variable name to runtime value to use for all variables
     * defined in the requestString.
     */
    public $variables;

    /**
     * @var string|null The name of the operation to use if requestString contains multiple
     * possible operations. Can be omitted if requestString contains only
     * one operation.
     */
    public $operationName;
}
