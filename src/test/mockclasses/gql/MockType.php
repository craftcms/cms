<?php
namespace craft\test\mockclasses\gql;

use craft\gql\common\SchemaObject;

/**
 * Class MockType
 */
class MockType extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'mockType';
    }
}
