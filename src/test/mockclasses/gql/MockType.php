<?php

namespace craft\test\mockclasses\gql;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class MockType
 */
class MockType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'mockType';

    /**
     * Returns a singleton instance to ensure one type per schema.
     *
     * @return MockType
     */
    public static function getType(): MockType
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self());
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'mockType';
    }

    public function serialize($value)
    {
        return 'mock';
    }

    public function parseValue($value)
    {
        return 'mock';
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        return 'mock';
    }
}
