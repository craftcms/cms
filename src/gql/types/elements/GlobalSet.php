<?php
namespace craft\gql\types\elements;

use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class GlobalSet
 */
class GlobalSet extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            GlobalSetInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }
}
