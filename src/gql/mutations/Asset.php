<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Volume;
use craft\elements\Asset as AssetElement;
use craft\gql\arguments\mutations\Asset as AssetMutationArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\Asset as AssetResolver;
use craft\gql\types\generators\AssetType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Asset extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateAssets()) {
            return [];
        }

        $mutationList = [];

        $createDeleteMutation = false;

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $scope = 'volumes.' . $volume->uid;

            $canCreate = Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                $mutation = static::createSaveMutation($volume);
                $mutationList[$mutation['name']] = $mutation;
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteAsset'] = [
                'name' => 'deleteAsset',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [Craft::createObject(AssetResolver::class), 'deleteAsset'],
                'description' => 'Delete an asset.',
                'type' => Type::boolean()
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-volume save mutation.
     *
     * @param Volume $volume
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function createSaveMutation(Volume $volume): array
    {
        $mutationName = AssetElement::gqlMutationNameByContext($volume);
        $mutationArguments = AssetMutationArguments::getArguments();
        $generatedType = AssetType::generateType($volume);

        $resolver = Craft::createObject(AssetResolver::class);
        $resolver->setResolutionData('volume', $volume);
        static::prepareResolver($resolver, $volume->getFields());

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY));

        return [
            'name' => $mutationName,
            'description' => 'Save an asset.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveAsset'],
            'type' => $generatedType
        ];
    }
}
