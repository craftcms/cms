<?php
namespace craft\gql\types;

use craft\elements\Asset as AssetElement;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class AssetType
 */
class Asset extends BaseType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [AssetInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var AssetElement $source */
        $fieldName = $resolveInfo->fieldName;

        if (StringHelper::substr($fieldName, 0, 6) === 'volume') {
            $volume = $source->getVolume();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 6));

            return $volume->$property ?? null;
        }

        if (StringHelper::substr($fieldName, 0, 6) === 'folder') {
            $folder = $source->getFolder();
            $property = StringHelper::lowercaseFirst(StringHelper::substr($fieldName, 6));

            return $folder->$property ?? null;
        }

        return $source->$fieldName;
    }

}
