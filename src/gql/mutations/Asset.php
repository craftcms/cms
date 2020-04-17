<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\elements\Asset as AssetElement;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\mutations\Asset as AssetMutationArguments;
use craft\gql\arguments\mutations\Draft as DraftMutationArguments;
use craft\gql\arguments\mutations\Entry as EntryMutationArguments;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\CreateDraft;
use craft\gql\resolvers\mutations\DeleteAsset;
use craft\gql\resolvers\mutations\DeleteEntry;
use craft\gql\resolvers\mutations\PublishDraft;
use craft\gql\resolvers\mutations\SaveAsset;
use craft\gql\resolvers\mutations\SaveDraft;
use craft\gql\resolvers\mutations\SaveEntry;
use craft\gql\types\generators\AssetType;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\Section;
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
                'resolve' => [new DeleteAsset(), 'resolve'],
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
    protected static function createSaveMutation(Volume $volume): array
    {
        $mutationName = AssetElement::gqlMutationNameByContext($volume);
        $contentFields = $volume->getFields();
        $assetMutationArguments = AssetMutationArguments::getArguments();
        $contentFieldHandles = [];
        $valueNormalizers = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();
            $assetMutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;

            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $valueNormalizers[$contentField->handle] = $configArray['normalizeValue'];
            }
        }

        $resolverData = [
            'volume' => $volume,
            'contentFieldHandles' => $contentFieldHandles,
        ];

        $generatedType = AssetType::generateType($volume);

        return [
            'name' => $mutationName,
            'description' => 'Save an asset.',
            'args' => $assetMutationArguments,
            'resolve' => [new SaveAsset($resolverData, $valueNormalizers), 'resolve'],
            'type' => $generatedType
        ];;
    }
}
