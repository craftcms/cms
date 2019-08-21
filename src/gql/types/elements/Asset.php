<?php
namespace craft\gql\types\elements;

use craft\elements\Asset as AssetElement;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\base\ObjectType;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Asset
 */
class Asset extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            AssetInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }
}
