<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\AssetTransformContext;
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

        if (! empty($arguments) && Gql::isAssetTransformField($fieldName)) {
            $transform = Gql::prepareTransformArguments($arguments);

            return Gql::resolveAssetTransform($source, $transform, $fieldName);
        }

        $transform = app(AssetTransformContext::class)->get($source);

        if ($transform !== null && Gql::isAssetTransformField($fieldName)) {
            return Gql::resolveAssetTransform(
                $source,
                $transform->definition,
                $fieldName,
            );
        }

        if ($fieldName === 'srcset') {
            return $source->getSrcset($arguments['sizes']);
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
