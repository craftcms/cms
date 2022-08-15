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
use craft\helpers\MoneyHelper;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class Money implements the Money scalar type for GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Money extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    public $name = 'Money';

    /**
     * @var string
     */
    public $description = 'The `Money` scalar type represents a money value as a string.';

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return Money
     */
    public static function getType(): Money
    {
        return GqlEntityRegistry::getEntity(static::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self());
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Money';
    }

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Money\Money) {
            $value = MoneyHelper::toString($value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        if (!is_int($value) && !is_float($value) && !is_null($value)) {
            throw new GqlException('Money must be either a float, an integer, or null.');
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        // Treat strings as floats
        if ($valueNode instanceof StringValueNode || $valueNode instanceof FloatValueNode) {
            return (float)$valueNode->value;
        }

        if ($valueNode instanceof IntValueNode) {
            return (int)$valueNode->value;
        }

        if ($valueNode instanceof NullValueNode) {
            return null;
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("Money must be either a float or an integer.");
    }
}
