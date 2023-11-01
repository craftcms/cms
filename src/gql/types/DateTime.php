<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\errors\GqlException;
use craft\gql\base\SingularTypeInterface;
use craft\gql\directives\FormatDateTime;
use craft\gql\GqlEntityRegistry;
use craft\helpers\DateTimeHelper;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class DateTime implements the Datetime scalar type for GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class DateTime extends ScalarType implements SingularTypeInterface
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
     * @return DateTime
     */
    public static function getType(): DateTime
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self());
    }

    /**
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
        // The value not being a datetime would indicate an already formatted date.
        if ($value instanceof \DateTime) {
            $value = $value->format(FormatDateTime::DEFAULT_FORMAT);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        if (is_string($value)) {
            return new \DateTime($value);
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("DateTime must be a string");
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            $date = new \DateTime($valueNode->value);
            // if we don't have a location, then it's timezone_type 1 (e.g. +01:00) or 2 (e.g. CEST)
            // and we need to do something about it
            if (!$date->getTimezone()->getLocation()) {
                $date = DateTimeHelper::toTimezoneTypeThree($date);
            }
            return $date;
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("DateTime must be a string");
    }
}
