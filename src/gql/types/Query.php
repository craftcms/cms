<?php
namespace craft\gql\types;

use craft\errors\GqlException;
use craft\gql\common\SchemaObject;

/**
 * Class Query
 */
class Query extends SchemaObject
{
    /**
     * @inheritdoc
     * @throws GqlException if class called incorrectly.
     */
    public static function getFields(): array
    {
        throw new GqlException('Query type should not have any fields listed statically. Fields must be set at type register time.');
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'Query';
    }
}
