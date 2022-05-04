<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * ExecuteGqlQueryEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.11
 */
class ExecuteGqlQueryEvent extends Event
{
    /**
     * @var int|null The id of the schema used for this request.
     * @since 3.4.0
     */
    public ?int $schemaId = null;

    /**
     * @var string The GraphQL query being executed
     */
    public string $query;

    /**
     * @var array|null The variables used for this query.
     */
    public ?array $variables = null;

    /**
     * @var string|null The name of the operation to use if requestString contains multiple possible operations.
     */
    public ?string $operationName = null;

    /**
     * @var mixed The context that is shared between all resolvers.
     */
    public mixed $context = null;

    /**
     * @var mixed The root value to use when resolving the top-level object fields.
     */
    public mixed $rootValue = null;

    /**
     * @var array|null The query result to be returned.
     */
    public ?array $result = null;
}
