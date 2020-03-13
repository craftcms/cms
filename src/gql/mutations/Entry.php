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
use craft\gql\arguments\elements\EntryMutation as EntryMutationArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\SaveEntry;
use craft\gql\types\generators\EntryType;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
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

        $allEntryTypes = Craft::$app->getSections()->getAllEntryTypes();

        $mutationList = [];

        foreach ($allEntryTypes as $entryType) {
            $isAllowedSection = in_array($entryType->sectionId, $allowedSectionIds, true);
            $isAllowedEntryType = in_array($entryType->uid, $allowedEntryTypeUids, true);

            if ($isAllowedEntryType && $isAllowedSection) {
                // Create a mutation for each entry type
                foreach (static::createMutations($entryType) as $mutation) {
                    $mutationList[$mutation['name']] = $mutation;
                }
            }
        }

        return $mutationList;
    }

    public static function createMutations(EntryTypeModel $entryType): array
    {
        $mutations = [];

        /** @var EntryTypeModel $mutationName */
        $mutationName = EntryElement::gqlMutationNameByContext($entryType);
        $contentFields = $entryType->getFields();
        $mutationArguments = EntryMutationArguments::getArguments();
        $contentFieldHandles = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlInputType();
            $mutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;
        }

        $section = $entryType->getSection();

        switch ($section->type) {
            case Section::TYPE_SINGLE:
                $description = 'Create a new “' . $entryType->name . '” entry.';
                unset($mutationArguments['authorId'], $mutationArguments['id'], $mutationArguments['uid']);
                break;
            case Section::TYPE_STRUCTURE:
                $mutationArguments['newParentId'] = [
                    'name' => 'newParentId',
                    'type' => Type::id(),
                    'description' => 'The ID of the parent entry.'
                ];
            default:
                $description = 'Create a new “' . $entryType->name . '” entry in the “' . $section->name . '” section.';
        }

        $resolver = new SaveEntry([
            'section' => $section,
            'entryType' => $entryType,
            'contentFieldHandles' => $contentFieldHandles
        ]);

        $mutations[] = [
            'name' => $mutationName,
            'description' => $description,
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'resolve'],
            'type' => EntryType::generateType($entryType)
        ];

        return $mutations;
    }
}
