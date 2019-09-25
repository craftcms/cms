<?php
namespace craft\test\mockclasses\gql;

use craft\gql\base\ObjectType;

/**
 * Class MockType
 */
class MockType extends ObjectType
{
    public static function getType()
    {
        return new self([
            'name' => static::getName()
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'mockType';
    }
}
