<?php
namespace craft\gql\types;

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
     * @var self
     */
    protected static $instance;

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return DateTimeType
     */
    public static function instance(): DateTimeType
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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
