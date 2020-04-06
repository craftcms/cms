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
    public $name = 'QueryArgument';

    /**
     * @var string
     */
    public $description = 'The `QueryArgument` scalar type represents a value to be using in Craft element queries. It can be both an integer or a string.';

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
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self());
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
            throw new GqlException("QueryArgument must be either a string or an integer.");
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
        throw new GqlException("QueryArgument must be either a string or an integer.");
    }
}
