<?php
namespace craft\gql\types;

use craft\gql\interfaces\elements\Element as ElementInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Element
 */
class Element extends BaseType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [ElementInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        return $source->{$resolveInfo->fieldName};
    }
}
