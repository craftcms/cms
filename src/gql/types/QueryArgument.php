<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class QueryArgument
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.14
 */
class QueryArgument extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'QueryParameter';

    /**
     * @var string
     */
    public $description = 'The `QueryParameter` scalar type represents a value to be using in Craft element queries. It can be both an integer or a string.';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return QueryArgument
     */
    public static function getType(): QueryArgument
    {
        return GqlEntityRegistry::getEntity(self::class) ?: GqlEntityRegistry::createEntity(self::class, new self());
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'QueryParameter';
    }

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        // The value not being a datetime would indicate an already formatted date.
        if (!is_int($value) && !is_string($value)) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        if (!is_int($value) && !is_string($value)) {
            throw new GqlException("QueryParameter must be either a string or an integer.");
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            return (string)$valueNode->value;
        }

        if ($valueNode instanceof IntValueNode) {
            return (int)$valueNode->value;
        }

        // Intentionally without message, as all information already in wrapped Exception
        throw new GqlException("QueryParameter must be either a string or an integer.");
    }
}
