<?php
namespace craft\gql\resolvers\elements;

use craft\gql\resolvers\BaseResolver;

/**
 * Class BaseElement
 */
abstract class BaseElement extends BaseResolver
{
    /**
     * Returns a list of all the arguments that can be accepted as arrays.
     *
     * @return array
     */
    public static function getArrayableArguments(): array
    {
        return [
            'status',
            'siteId',
            'id',
            'uid',
            'title',
            'slug',
            'uri',
            'ref',
        ];
    }
}
