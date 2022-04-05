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
     * @var string The access token used for this request.
     * @deprecated in 3.4.0. Use [[schemaId]] instead to determine schema used.
     */
    public $accessToken;

    /**
     * @var int The id of the schema used for this request.
     * @since 3.4.0
     */
    public $schemaId;

    /**
     * @var string The GraphQL query being executed
     */
    public $query;

    /**
     * @var array|null The variables used for this query.
     */
    public $variables;

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
