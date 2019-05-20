<?php
namespace craft\gql\types;

use craft\gql\TypeRegistry;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class DateTime
 */
class DateTimeType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'DateTime';

    /**
     * @var string
     */
    public $description = 'The `DateTime` scalar type represents a point in time.';

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return DateTimeType
     */
    public static function getType(): DateTimeType
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new self());
    }

    /**
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'DateTime';
    }

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        return $value->getTimestamp();
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        return (string) $value;
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            return (string) $valueNode->value;
        }

        // Intentionally without message, as all information already in wrapped Exception
        throw new \Exception();
    }
}
