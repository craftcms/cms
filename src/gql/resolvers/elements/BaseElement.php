<?php
namespace craft\gql\resolvers\elements;

use craft\gql\resolvers\BaseResolver;

/**
 * Class BaseElement
 */
abstract class BaseElement extends BaseResolver
{
    /**
     * @inheritdoc
     */
    public static function getArrayableArguments(): array
    {
        return array_merge(parent::getArrayableArguments(), [
            'status',
            'siteId',
            'title',
            'slug',
            'uri',
            'ref',
        ]);
    }
}
