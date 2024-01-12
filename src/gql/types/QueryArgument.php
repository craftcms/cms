<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\errors\GqlException;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class QueryArgument implements the QueryArgument scalar type for GraphQL which can be a string, a number or a boolean value.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.14
 */
class QueryArgument extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    public $name = 'QueryArgument';

    /**
     * @var string
     */
    public $description = 'The `QueryArgument` scalar type represents a value to be using in Craft element queries. It can be an integer, a string, or a boolean value.';

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
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self());
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'QueryArgument';
    }

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        // If it's neither int or string, attempt to make it a string.
        if (!is_int($value) && !is_string($value) && !is_bool($value)) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        if (!is_int($value) && !is_string($value) && !is_bool($value)) {
            throw new GqlException("QueryArgument must be either a string, an integer, or a boolean value.");
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            return $valueNode->value;
        }

        if ($valueNode instanceof IntValueNode) {
            return (int)$valueNode->value;
        }

        if ($valueNode instanceof BooleanValueNode) {
            return $valueNode->value;
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("QueryArgument must be either a string, an integer, or a boolean value.");
    }
}
