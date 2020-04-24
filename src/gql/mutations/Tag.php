<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\elements\Tag as TagElement;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\DeleteTag;
use craft\gql\resolvers\mutations\SaveTag;
use craft\gql\types\generators\TagType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\TagGroup;
use GraphQL\Type\Definition\Type;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Tag extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateTags()) {
            return [];
        }

        $mutationList = [];

        $createDeleteMutation = false;

        foreach (Craft::$app->getTags()->getAllTagGroups() as $tagGroup) {
            $scope = 'taggroups.' . $tagGroup->uid;

            if (Gql::canSchema($scope, 'save')) {
                // Create a mutation for the tag group
                $mutation = static::createSaveMutation($tagGroup);
                $mutationList[$mutation['name']] = $mutation;
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteTag'] = [
                'name' => 'deleteTag',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new DeleteTag(), 'resolve'],
                'description' => 'Delete a tag.',
                'type' => Type::boolean()
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-tag-group save mutation.
     *
     * @param EntryTypeModel $tagGroup
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function createSaveMutation(TagGroup $tagGroup): array
    {
        $mutationName = TagElement::gqlMutationNameByContext($tagGroup);
        $contentFields = $tagGroup->getFields();
        $mutationArguments = ElementMutationArguments::getArguments();
        $contentFieldHandles = [];
        $valueNormalizers = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();
            $mutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;

            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $valueNormalizers[$contentField->handle] = $configArray['normalizeValue'];
            }
        }

        $description = 'Save the “' . $tagGroup->name . '” tag.';

        $resolverData = [
            'tagGroup' => $tagGroup,
            'contentFieldHandles' => $contentFieldHandles,
        ];

        $generatedType = TagType::generateType($tagGroup);

        return [
            'name' => $mutationName,
            'description' => $description,
            'args' => $mutationArguments,
            'resolve' => [new SaveTag($resolverData, $valueNormalizers), 'resolve'],
            'type' => $generatedType
        ];;
    }
}
