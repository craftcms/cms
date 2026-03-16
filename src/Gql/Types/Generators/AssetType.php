<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset as AssetInterface;
use CraftCms\Cms\Gql\Types\Elements\Asset;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Volumes;

class AssetType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        $volumes = Volumes::getAllVolumes();
        $gqlTypes = [];

        foreach ($volumes as $volume) {
            $requiredContexts = AssetElement::gqlScopesByContext($volume);

            if (! GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            // Generate a type for each volume
            $type = static::generateType($volume);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = AssetElement::gqlTypeName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new Asset([
            'name' => $typeName,
            'fields' => function () use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context);
                $assetFields = array_merge(AssetInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                return Gql::prepareFieldDefinitions($assetFields, $typeName);
            },
        ]));
    }
}
