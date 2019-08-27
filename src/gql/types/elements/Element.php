<?php
namespace craft\gql\types\elements;

use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Element
 */
class Element extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [ElementInterface::getType()];
        parent::__construct($config);
    }
}
