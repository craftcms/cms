<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use Craft;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Gql\Arguments\Mutations\Asset as AssetMutationArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\Mutations\Asset as AssetResolver;
use CraftCms\Cms\Gql\Types\Generators\AssetType;
use CraftCms\Cms\Support\Facades\Volumes;
use GraphQL\Type\Definition\Type;

class Asset extends Mutation
{
    public static function getMutations(): array
    {
        if (! GqlHelper::canMutateAssets()) {
            return [];
        }

        $mutationList = [];
        $createDeleteMutation = false;

        foreach (Volumes::getAllVolumes() as $volume) {
            $scope = 'volumes.'.$volume->uid;

            $canCreate = GqlHelper::canSchema($scope, 'create');
            $canSave = GqlHelper::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                $mutation = static::createSaveMutation($volume);
                $mutationList[$mutation['name']] = $mutation;
            }

            if (! $createDeleteMutation && GqlHelper::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteAsset'] = [
                'name' => 'deleteAsset',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [Craft::createObject(AssetResolver::class), 'deleteAsset'],
                'description' => 'Delete an asset.',
                'type' => Type::boolean(),
            ];
        }

        return $mutationList;
    }

    public static function createSaveMutation(Volume $volume): array
    {
        $mutationArguments = AssetMutationArguments::getArguments();
        $generatedType = AssetType::generateType($volume);

        $resolver = Craft::createObject(AssetResolver::class);
        $resolver->setResolutionData('volume', $volume);

        static::prepareResolver($resolver, $volume->getCustomFields());

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY));

        return [
            'name' => "save_{$volume->handle}_Asset",
            'description' => 'Save an asset.',
            'args' => $mutationArguments,
            'resolve' => $resolver->saveAsset(...),
            'type' => $generatedType,
        ];
    }
}
