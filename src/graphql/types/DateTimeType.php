<?php
namespace craft\graphql\types;

use craft\helpers\DateTimeHelper;
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
     * @inheritdoc
     */
    public function serialize($value)
    {
        return (string) $value;
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
