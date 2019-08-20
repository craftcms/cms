<?php
namespace craft\gql\types\elements;

use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\types\BaseType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class GlobalSet
 */
class GlobalSet extends BaseType
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

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var GlobalSetElement $source */
        $fieldName = $resolveInfo->fieldName;

        return $source->$fieldName;
    }
}
