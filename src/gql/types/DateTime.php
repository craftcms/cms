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
use craft\helpers\Json;
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
            if (Json::isJsonObject($value)) {
                $dateArray = Json::decodeIfJson($value);
                // the json value should contain: date, time or datetime keys and can contain tz or timezone
                $this->_validateJsonDate($dateArray);
                return $dateArray;
            } else {
                return DateTimeHelper::toDateTime($value);
            }
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("DateTime must be a string or a JSON string");
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            if (Json::isJsonObject($valueNode->value)) {
                $dateArray = Json::decodeIfJson($valueNode->value);
                $this->_validateJsonDate($dateArray);
                return $dateArray;
            } else {
                return DateTimeHelper::toDateTime($valueNode->value);
            }
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException("DateTime must be a string or a JSON string");
    }

    /**
     * Ensure that the json object passed to the request contains at least "date", "time" or "datetime" values,
     * doesn't have a "tz" key and the "timezone" (if provided) is in a type 3 notation.
     *
     * @param array $json
     * @return void
     * @throws GqlException
     */
    private function _validateJsonDate(array $json): void
    {
        // The json value should contain: date, time or datetime keys and can contain a timezone key.
        if (empty($json['date']) && empty($json['time']) && empty($json['datetime'])) {
            throw new GqlException("DateTime JSON must contain `date`, `time` or `datetime` values");
        }
        // Combination of "date" and "tz" will assume the value is coming from the database
        // and yield potentially wrong results, so we're banning the "tz" key.
        if (isset($json['tz'])) {
            throw new GqlException("If you wish to use timezone in your DateTime JSON, use the `timezone` key");
        }
        // We're also enforcing timezone in type 3 notation
        if (!empty($json['timezone']) && !str_contains($json['timezone'], '/')) {
            throw new GqlException("DateTime timezone must be in a `Europe/London` notation.");
        }
    }
}
