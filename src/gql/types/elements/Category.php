<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\Category as CategoryElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            CategoryInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var CategoryElement $source */
        $fieldName = $resolveInfo->fieldName;

        switch ($fieldName) {
            case 'groupId':
                return $source->groupIdp;
            case 'groupHandle':
                return $source->getGroup()->handle;
        }

        return $source->$fieldName;
    }
}
