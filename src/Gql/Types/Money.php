<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Money as MoneyHelper;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Override;

class Money extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    #[Override]
    public $name = 'Money';

    /**
     * @var string
     */
    #[Override]
    public $description = 'The `Money` scalar type represents a money value as a string.';

    public static function getType(): Money
    {
        return GqlEntityRegistry::getOrCreate(static::getName(), fn () => new self);
    }

    public static function getName(): string
    {
        return 'Money';
    }

    public function serialize($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Money\Money) {
            return MoneyHelper::toString($value);
        }

        return $value;
    }

    public function parseValue($value)
    {
        if (! is_int($value) && ! is_float($value) && ! is_null($value)) {
            throw new GqlException('Money must be either a float, an integer, or null.');
        }

        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null): float|int|null
    {
        return match (true) {
            // Treat strings as floats
            $valueNode instanceof StringValueNode, $valueNode instanceof FloatValueNode => (float) $valueNode->value,
            $valueNode instanceof IntValueNode => (int) $valueNode->value,
            $valueNode instanceof NullValueNode => null,
            default => throw new GqlException('Money must be either a float or an integer.'),
        };
    }
}
