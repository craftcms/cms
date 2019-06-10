<?php
namespace craft\gql\types;

use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class GlobalSetType
 */
class GlobalSet extends BaseType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [GlobalSetInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var GlobalSetElement $source */
        $fieldName = $resolveInfo->fieldName;

        return $source->$fieldName;
    }
}
