<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Directives\FormatDateTime;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Json;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Override;

class DateTime extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    #[Override]
    public $name = 'DateTime';

    /**
     * @var string
     */
    #[Override]
    public $description = 'The `DateTime` scalar type represents a point in time.';

    /**
     * @var bool Whether parsed dates should be set to the system time zone
     */
    public bool $setToSystemTimeZone = true;

    public static function getType(): DateTime
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self);
    }

    public static function getName(): string
    {
        return 'DateTime';
    }

    public function serialize($value)
    {
        // The value not being a datetime would indicate an already formatted date.
        if ($value instanceof \DateTime) {
            return $value->format(FormatDateTime::DEFAULT_FORMAT);
        }

        return $value;
    }

    public function parseValue($value)
    {
        if (is_string($value)) {
            return DateTimeHelper::toDateTime(
                Json::decodeIfJson($value),
                setToSystemTimeZone: $this->setToSystemTimeZone,
            );
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException('DateTime must be a string.');
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            return DateTimeHelper::toDateTime(
                Json::decodeIfJson($valueNode->value),
                setToSystemTimeZone: $this->setToSystemTimeZone,
            );
        }

        // This message will be lost by the wrapping exception, but it feels good to provide one.
        throw new GqlException('DateTime must be a string.');
    }
}
