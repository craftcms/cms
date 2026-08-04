<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Gql;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class MockType
 */
class MockType extends ScalarType implements SingularTypeInterface
{
    #[\Override]
    public string $name = 'mockType';

    /**
     * Returns a singleton instance to ensure one type per schema.
     */
    public static function getType(): MockType
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self);
    }

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'mockType';
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        return 'mock';
    }
}
