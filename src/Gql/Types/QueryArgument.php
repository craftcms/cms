<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Override;

class QueryArgument extends ScalarType implements SingularTypeInterface
{
    /**
     * @var string
     */
    #[Override]
    public $name = 'QueryArgument';

    /**
     * @var string
     */
    #[Override]
    public $description = 'The `QueryArgument` scalar type represents a value to be using in Craft element queries. It can be an integer, a string, or a boolean value.';

    public static function getType(): QueryArgument
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self);
    }

    public static function getName(): string
    {
        return 'QueryArgument';
    }

    public function serialize($value): bool|int|string
    {
        // If it's neither int or string, attempt to make it a string.
        if (! is_int($value) && ! is_string($value) && ! is_bool($value)) {
            return (string) $value;
        }

        return $value;
    }

    public function parseValue($value): bool|int|string
    {
        if (! is_int($value) && ! is_string($value) && ! is_bool($value)) {
            throw new GqlException('QueryArgument must be either a string, an integer, or a boolean value.');
        }

        return $value;
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        return match (true) {
            $valueNode instanceof StringValueNode => $valueNode->value,
            $valueNode instanceof IntValueNode => (int) $valueNode->value,
            $valueNode instanceof BooleanValueNode => $valueNode->value,
            default => throw new GqlException('QueryArgument must be either a string, an integer, or a boolean value.'),
        };
    }
}
