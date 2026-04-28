<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class Asset extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            AssetInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var AssetElement $source */
        $fieldName = $resolveInfo->fieldName;

        if (! empty($arguments) && in_array($fieldName, ['url', 'width', 'height'], true)) {
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
