<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\elements\Tag as TagElement;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\Tag as TagResolver;
use craft\gql\types\generators\TagType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\TagGroup;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

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
                'resolve' => [Craft::createObject(TagResolver::class), 'deleteTag'],
                'description' => 'Delete a tag.',
                'type' => Type::boolean(),
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-tag-group save mutation.
     *
     * @param TagGroup $tagGroup
     * @return array
     * @throws InvalidConfigException
     */
    public static function createSaveMutation(TagGroup $tagGroup): array
    {
        $mutationName = TagElement::gqlMutationNameByContext($tagGroup);
        $mutationArguments = ElementMutationArguments::getArguments();
        $generatedType = TagType::generateType($tagGroup);

        /** @var TagResolver $resolver */
        $resolver = Craft::createObject(TagResolver::class);
        $resolver->setResolutionData('tagGroup', $tagGroup);
        static::prepareResolver($resolver, $tagGroup->getCustomFields());

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY));

        return [
            'name' => $mutationName,
            'description' => 'Save the “' . $tagGroup->name . '” tag.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveTag'],
            'type' => $generatedType,
        ];
    }
}
