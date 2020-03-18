<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\gql\arguments\elements\DraftMutation as DraftMutationArguments;
use craft\gql\arguments\elements\EntryMutation as EntryMutationArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\CreateDraft;
use craft\gql\resolvers\mutations\DeleteEntry;
use craft\gql\resolvers\mutations\PublishDraft;
use craft\gql\resolvers\mutations\SaveDraft;
use craft\gql\resolvers\mutations\SaveEntry;
use craft\gql\types\generators\EntryType;
use craft\helpers\Db;
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

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('write');
        $allowedSectionUids = $pairs['sections'];
        $allowedSectionIds = Db::idsByUids(Table::SECTIONS, $allowedSectionUids);
        $allowedEntryTypeUids = $pairs['entrytypes'];

        $mutationList = [];

        foreach (Craft::$app->getSections()->getAllEntryTypes() as $entryType) {
            $isAllowedSection = in_array($entryType->sectionId, $allowedSectionIds, true);
            $isAllowedEntryType = in_array($entryType->uid, $allowedEntryTypeUids, true);

            if ($isAllowedEntryType && $isAllowedSection) {
                // Create a mutation for each entry type
                foreach (static::createMutations($entryType) as $mutation) {
                    $mutationList[$mutation['name']] = $mutation;
                }
            }
        }

        foreach (self::createCommonMutations() as $commonMutation) {
            $mutationList[$commonMutation['name']] = $commonMutation;
        }

        return $mutationList;
    }

    /**
     * Create the per-entry-type mutations.
     *
     * @param EntryTypeModel $entryType
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected static function createMutations(EntryTypeModel $entryType): array
    {
        $mutations = [];

        /** @var EntryTypeModel $mutationName */
        $mutationName = EntryElement::gqlMutationNameByContext($entryType);
        $contentFields = $entryType->getFields();
        $entryMutationArguments = EntryMutationArguments::getArguments();
        $draftMutationArguments = DraftMutationArguments::getArguments();
        $contentFieldHandles = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlInputType();
            $entryMutationArguments[$contentField->handle] = $contentFieldType;
            $draftMutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;
        }

        $section = $entryType->getSection();

        switch ($section->type) {
            case Section::TYPE_SINGLE:
                $description = 'Save the “' . $entryType->name . '” entry.';
                $draftDescription = 'Save the “' . $entryType->name . '” draft.';

                unset($entryMutationArguments['authorId'], $entryMutationArguments['id'], $entryMutationArguments['uid']);
                unset($draftMutationArguments['authorId'], $draftMutationArguments['id'], $draftMutationArguments['uid']);
                break;
            case Section::TYPE_STRUCTURE:
                $entryMutationArguments['newParentId'] = [
                    'name' => 'newParentId',
                    'type' => Type::id(),
                    'description' => 'The ID of the parent entry.'
                ];
            default:
                $description = 'Save a “' . $entryType->name . '” entry in the “' . $section->name . '” section.';
                $draftDescription = 'Save a “' . $entryType->name . '” entry draft in the “' . $section->name . '” section.';
        }

        $resolverData = [
            'section' => $section,
            'entryType' => $entryType,
            'contentFieldHandles' => $contentFieldHandles
        ];

        $saveResolver = new SaveEntry($resolverData);

        $generatedType = EntryType::generateType($entryType);

        $mutations[] = [
            'name' => $mutationName,
            'description' => $description,
            'args' => $entryMutationArguments,
            'resolve' => [$saveResolver, 'resolve'],
            'type' => $generatedType
        ];

        $draftResolver = new SaveDraft([
            'section' => $section,
            'entryType' => $entryType,
            'contentFieldHandles' => $contentFieldHandles
        ]);

        $mutations[] = [
            'name' => StringHelper::replaceEnding($mutationName, '_Entry', '_Draft'),
            'description' => $draftDescription,
            'args' => $draftMutationArguments,
            'resolve' => [$draftResolver, 'resolve'],
            'type' => $generatedType
        ];

        return $mutations;
    }

    /**
     * Create mutations common to all entries, regardless of entry types.
     *
     * @return array
     */
    protected static function createCommonMutations(): array
    {
        return [
            [
                'name' => 'deleteEntry',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new DeleteEntry(), 'resolve'],
                'description' => 'Delete an entry.',
                'type' => Type::boolean()
            ],
            [
                'name' => 'createDraft',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new CreateDraft(), 'resolve'],
                'description' => 'Create a draft for an entry and return the draft ID.',
                'type' => Type::id()
            ],
            [
                'name' => 'publishDraft',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new PublishDraft(), 'resolve'],
                'description' => 'Publish a draft for the entry and return the entry ID.',
                'type' => Type::id()
            ],
        ];
    }
}
