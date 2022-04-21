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
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class Number implements the Number scalar type for GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Number extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    public $name = 'Number';

    /**
     * @var string
     */
    public $description = 'The `Number` scalar type represents a number that can be a float, an integer or a null value.';

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return Number
     */
    public static function getType(): Number
    {
        return GqlEntityRegistry::getEntity(static::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self());
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'Number';
    }

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        if (is_numeric($value)) {
            if ((int)$value == $value) {
                return (int)$value;
            }
            if ((float)$value == $value) {
                return (float)$value;
            }
        }

        if (empty($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        if (!is_int($value) && !is_float($value) && !is_null($value)) {
            throw new GqlException('Number must be either a float, an integer, or null.');
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
        throw new GqlException("Number must be either a float or an integer.");
    }
}
