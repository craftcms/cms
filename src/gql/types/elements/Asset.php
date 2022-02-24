<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\Asset as AssetElement;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\helpers\Gql;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            AssetInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var AssetElement $source */
        $fieldName = $resolveInfo->fieldName;

        if (!empty($arguments) && in_array($fieldName, ['url', 'width', 'height'], true)) {
            $transform = Gql::prepareTransformArguments($arguments);

            switch ($fieldName) {
                case 'url':
                    return $source->getUrl($transform);
                case 'width':
                    return $source->getWidth($transform);
                case 'height':
                    return $source->getHeight($transform);
            }
        }

        if ($fieldName === 'srcset') {
            return $source->getSrcset($arguments['sizes']);
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
