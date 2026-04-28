<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Override;

class Number extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    #[Override]
    public $name = 'Number';

    /**
     * @var string
     */
    #[Override]
    public $description = 'The `Number` scalar type represents a number that can be a float, an integer or a null value.';

    public static function getType(): Number
    {
        return GqlEntityRegistry::getOrCreate(static::getName(), fn () => new self);
    }

    public static function getName(): string
    {
        return 'Number';
    }

    public function serialize($value)
    {
        if (! is_numeric($value)) {
            return empty($value) ? null : $value;
        }

        return match (true) {
            (int) $value == $value => (int) $value,
            (float) $value == $value => (float) $value,
            default => $value,
        };
    }

    public function parseValue($value)
    {
        if (! is_int($value) && ! is_float($value) && ! is_null($value)) {
            throw new GqlException('Number must be either a float, an integer, or null.');
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
            default => throw new GqlException('Number must be either a float or an integer.'),
        };
    }
}
