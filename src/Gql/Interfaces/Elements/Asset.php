<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces\Elements;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\Arguments\Elements\Asset as AssetArguments;
use CraftCms\Cms\Gql\Arguments\Elements\User as UserArguments;
use CraftCms\Cms\Gql\Arguments\Transform;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Types\Generators\AssetType;
use CraftCms\Cms\Gql\Types\Generators\ImageAnalysisType;
use CraftCms\Cms\Gql\Types\Volume;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Asset extends Element
{
    #[Override]
    public static function getTypeGenerator(): string
    {
        return AssetType::class;
    }

    #[Override]
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the interface implemented by all assets.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        AssetType::generateTypes();

        return $type;
    }

    #[Override]
    public static function getName(): string
    {
        return 'AssetInterface';
    }

    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        // @TODO Remove the `uri` field for Assets.
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
            ...static::propertyFields(AssetElement::class, ['alt', 'volumeId', 'folderId', 'filename', 'extension', 'hasFocalPoint']),
            'focalPoint' => [
                'name' => 'focalPoint',
                'type' => Type::listOf(Type::float()),
                'description' => 'The focal point represented as an array with `x` and `y` keys, or null if it’s not an image.',
            ],
            'analysis' => [
                'name' => 'analysis',
                'type' => ImageAnalysisType::generateType(null),
                'description' => 'What was found in the image: its colors, and its keywords and the text in it, or null if it’s not an image or nothing has been found yet.',
            ],
            ...static::propertyFields(AssetElement::class, ['blurhash', 'placeholderDataUrl', 'kind']),
            'orientation' => [
                'name' => 'orientation',
                'type' => Type::string(),
                'description' => 'Whether the image is `landscape`, `portrait`, or `square`, or null if its dimensions aren’t known.',
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
                'description' => 'The file size in bytes.',
            ],
            'height' => [
                'name' => 'height',
                'args' => Transform::getArguments(),
                'type' => Type::int(),
                'description' => 'The height in pixels or null if it’s not an image.',
                'complexity' => function ($childrenComplexity, $args) {
                    if (empty($args)) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_SIMPLE_FIELD;
                    }

                    // Likely to be already generated
                    if (! empty($args['handle']) || ! empty($args['transform'])) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;
                    }

                    return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_CPU_HEAVY;
                },
            ],
            'width' => [
                'name' => 'width',
                'args' => Transform::getArguments(),
                'type' => Type::int(),
                'description' => 'The width in pixels or null if it’s not an image.',
                'complexity' => function ($childrenComplexity, $args) {
                    if (empty($args)) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_SIMPLE_FIELD;
                    }

                    // Likely to be already generated
                    if (! empty($args['handle']) || ! empty($args['transform'])) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;
                    }

                    return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_CPU_HEAVY;
                },
            ],
            'img' => [
                'name' => 'img',
                'type' => Type::string(),
                'description' => 'An `<img>` tag based on this asset.',
            ],
            'srcset' => [
                'name' => 'srcset',
                'type' => Type::string(),
                'args' => [
                    'sizes' => [
                        'name' => 'sizes',
                        'description' => 'A list of size descriptors. If you pass x-descriptors, it will be assumed that the image’s current width is the intended 1x width.',
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    ],
                ],
                'description' => 'Returns a `srcset` attribute value based on the given widths or x-descriptors.',
            ],
            'url' => [
                'name' => 'url',
                'args' => Transform::getArguments(),
                'type' => Type::string(),
                'description' => 'The full URL of the asset. This field accepts the same fields as the `transform` directive.',
                'complexity' => function ($childrenComplexity, $args) {
                    if (empty($args)) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_SIMPLE_FIELD;
                    }

                    // Likely to be already generated
                    if (! empty($args['handle']) || ! empty($args['transform'])) {
                        return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;
                    }

                    return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_CPU_HEAVY;
                },
            ],
            ...static::propertyFields(AssetElement::class, ['mimeType']),
            'format' => [
                'name' => 'format',
                'args' => Transform::getArguments(),
                'type' => Type::string(),
                'description' => 'Returns the file’s format.',
            ],
            ...static::propertyFields(AssetElement::class, ['path', 'dateModified']),
            'prev' => [
                'name' => 'prev',
                'type' => self::getType(),
                'args' => AssetArguments::getArguments(),
                'description' => 'Returns the previous element relative to this one, from a given set of criteria.',
                'complexity' => fn ($childrenComplexity, $args) => $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int) ! empty($args),
            ],
            'next' => [
                'name' => 'next',
                'type' => self::getType(),
                'args' => AssetArguments::getArguments(),
                'description' => 'Returns the next element relative to this one, from a given set of criteria.',
                'complexity' => fn ($childrenComplexity, $args) => $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int) ! empty($args),
            ],
        ]), self::getName());
    }

    /** @return array<string, FieldDefinitionConfig> */
    protected static function getConditionalFields(): array
    {
        $fields = [];

        if (GqlHelper::canSchema('settings.volumes')) {
            $fields['volume'] = [
                'name' => 'volume',
                'type' => Type::nonNull(Volume::getType()),
                'description' => 'The volume that the asset belongs to.',
            ];
        }

        if (GqlHelper::canQueryAllUsers()) {
            $fields += [
                ...static::propertyFields(AssetElement::class, ['uploaderId']),
                'uploader' => [
                    'name' => 'uploader',
                    'type' => User::getType(),
                    'args' => UserArguments::getArguments(),
                    'description' => 'The user who first added this asset (if known).',
                ],
            ];
        }

        return $fields;
    }
}
