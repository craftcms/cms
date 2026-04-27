<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Arguments\Transform as TransformArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Collection;

class Transform extends Directive
{
    public function __construct(array $config)
    {
        $args = &$config['args'];

        foreach ($args as &$argument) {
            $argument = new FieldArgument($argument);
        }

        parent::__construct($config);
    }

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

    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if (empty($arguments)) {
            return $value;
        }

        $transform = Gql::prepareTransformArguments($arguments);

        if ($value instanceof Asset) {
            return $value->setTransform($transform);
        }

        if ($value instanceof Collection || is_array($value)) {
            foreach ($value as $asset) {
                // If this somehow ended up being a mix of elements, don't explicitly fail, just set the transform on the asset elements
                if ($asset instanceof Asset) {
                    $asset->setTransform($transform);
                }
            }

            return $value;
        }

        if (! $source instanceof Asset) {
            return $value;
        }

        $allowTransform = match ($source->getMimeType()) {
            'image/gif' => Cms::config()->transformGifs,
            'image/svg+xml' => Cms::config()->transformSvgs,
            default => true,
        };

        if (! $allowTransform) {
            $transform = null;
        }

        return match ($resolveInfo->fieldName) {
            'format' => $source->getFormat($transform),
            'height' => $source->getHeight($transform),
            'mimeType' => $source->getMimeType($transform),
            'url' => $source->getUrl(
                transform: $transform,
                immediately: $arguments['immediately'] ?? Cms::config()->generateTransformsBeforePageLoad,
            ),
            'width' => $source->getWidth($transform),
            default => $value,
        };
    }
}
