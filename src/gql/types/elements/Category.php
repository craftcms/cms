<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\Category as CategoryElement;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            CategoryInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var CategoryElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'groupHandle' => $source->getGroup()->handle,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
