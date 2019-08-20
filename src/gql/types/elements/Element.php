<?php
namespace craft\gql\types\elements;

use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\BaseType;
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
