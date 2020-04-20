<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\mutations\Draft as DraftMutationArguments;
use craft\gql\arguments\mutations\Entry as EntryMutationArguments;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\CreateDraft;
use craft\gql\resolvers\mutations\DeleteEntry;
use craft\gql\resolvers\mutations\PublishDraft;
use craft\gql\resolvers\mutations\SaveDraft;
use craft\gql\resolvers\mutations\SaveEntry;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\Section;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Entry extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateEntries()) {
            return [];
        }

        $mutationList = [];

        $createDeleteMutation = false;
        $createDraftMutations = false;

        foreach (Craft::$app->getSections()->getAllEntryTypes() as $entryType) {
            $scope = 'entrytypes.' . $entryType->uid;
            $canCreate = Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                // Create a mutation for each entry type
                foreach (static::createSaveMutations($entryType, $canSave) as $mutation) {
                    $mutationList[$mutation['name']] = $mutation;
                }
            }

            if (!$createDraftMutations && $canSave) {
                $createDraftMutations = true;
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteEntry'] = [
                'name' => 'deleteEntry',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new DeleteEntry(), 'resolve'],
                'description' => 'Delete an entry.',
                'type' => Type::boolean()
            ];
        }

        if ($createDraftMutations) {
            $mutationList['createDraft'] = [
                'name' => 'createDraft',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new CreateDraft(), 'resolve'],
                'description' => 'Create a draft for an entry and return the draft ID.',
                'type' => Type::id()
            ];

            $mutationList['publishDraft'] = [
                'name' => 'publishDraft',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new PublishDraft(), 'resolve'],
                'description' => 'Publish a draft for the entry and return the entry ID.',
                'type' => Type::id()
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-entry-type save mutations.
     *
     * @param EntryTypeModel $entryType
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected static function createSaveMutations(EntryTypeModel $entryType, bool $createSaveDraftMutation): array
    {
        $mutations = [];

        $mutationName = EntryElement::gqlMutationNameByContext($entryType);
        $contentFields = $entryType->getFields();
        $entryMutationArguments = EntryMutationArguments::getArguments();
        $draftMutationArguments = DraftMutationArguments::getArguments();
        $contentFieldHandles = [];
        $valueNormalizers = [];

        $section = $entryType->getSection();

        switch ($section->type) {
            case Section::TYPE_SINGLE:
                $description = 'Save the “' . $entryType->name . '” entry.';
                $draftDescription = 'Save the “' . $entryType->name . '” draft.';

                unset($entryMutationArguments['authorId'], $entryMutationArguments['id'], $entryMutationArguments['uid']);
                unset($draftMutationArguments['authorId'], $draftMutationArguments['id'], $draftMutationArguments['uid']);
                break;
            case Section::TYPE_STRUCTURE:
                $entryMutationArguments = array_merge($entryMutationArguments, StructureArguments::getArguments());
            default:
                $description = 'Save a “' . $entryType->name . '” entry in the “' . $section->name . '” section.';
                $draftDescription = 'Save a “' . $entryType->name . '” entry draft in the “' . $section->name . '” section.';
        }

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlMutationArgumentType();
            $entryMutationArguments[$contentField->handle] = $contentFieldType;
            $draftMutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;

            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;
            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $valueNormalizers[$contentField->handle] = $configArray['normalizeValue'];
            }
        }

        $resolverData = [
            'section' => $section,
            'entryType' => $entryType,
            'contentFieldHandles' => $contentFieldHandles,
        ];

        $generatedType = EntryType::generateType($entryType);

        $mutations[] = [
            'name' => $mutationName,
            'description' => $description,
            'args' => $entryMutationArguments,
            'resolve' => [new SaveEntry($resolverData, $valueNormalizers), 'resolve'],
            'type' => $generatedType
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => StringHelper::replaceEnding($mutationName, '_Entry', '_Draft'),
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => [new SaveDraft($resolverData, $valueNormalizers), 'resolve'],
                'type' => $generatedType
            ];
        }

        return $mutations;
    }
}
