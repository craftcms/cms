<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Gql\Arguments\Transform as TransformArguments;
use CraftCms\Cms\Gql\AssetTransformContext;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;

class Transform extends Directive
{
    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate(static::name(), fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => TransformArguments::getArguments(),
            'description' => 'Returns a URL for an [asset transform](https://craftcms.com/docs/5.x/development/image-transforms.html). Accepts the same arguments you would use for a transform in Craft.',
        ]));
    }

    public static function name(): string
    {
        return 'transform';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if (empty($arguments)) {
            return $value;
        }

        $transform = Gql::prepareTransformArguments($arguments);
        $context = app(AssetTransformContext::class);
        $withTransform = fn (Asset $asset) => $context->set(clone $asset, $transform);

        if ($value instanceof Asset) {
            return $withTransform($value);
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($asset) => $asset instanceof Asset ? $withTransform($asset) : $asset);
        }

        if (is_array($value)) {
            return array_map(fn ($asset) => $asset instanceof Asset ? $withTransform($asset) : $asset, $value);
        }

        if (! $source instanceof Asset) {
            return $value;
        }

        return Gql::isAssetTransformField($resolveInfo->fieldName)
            ? Gql::resolveAssetTransform($source, $transform, $resolveInfo->fieldName)
            : $value;
    }
}
