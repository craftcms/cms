<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\gql\arguments\mutations\Draft as DraftMutationArguments;
use craft\gql\arguments\mutations\Entry as EntryMutationArguments;
use craft\gql\arguments\mutations\NestedEntry;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use craft\models\EntryType as EntryTypeModel;
use craft\models\Section;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

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
        $mutationList = [];
        $createDeleteMutation = false;
        $createDraftMutations = false;

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $scope = "sections.$section->uid";
            $isSingle = $section->type === Section::TYPE_SINGLE;
            $canCreate = !$isSingle && Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                // Create a mutation for each entry type
                foreach ($section->getEntryTypes() as $entryType) {
                    foreach (static::createSaveMutations($section, $entryType, $canSave) as $mutation) {
                        $mutationList[$mutation['name']] = $mutation;
                    }
                }
            }

            if (!$createDraftMutations && $canSave) {
                $createDraftMutations = true;
            }

            if (!$createDeleteMutation && !$isSingle && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        $fieldsService = Craft::$app->getFields();
        foreach ($fieldsService->getNestedEntryFieldTypes() as $type) {
            foreach ($fieldsService->getFieldsByType($type) as $field) {
                /** @var ElementContainerFieldInterface $field */
                $scope = "nestedentryfields.$field->uid";
                $canCreate = Gql::canSchema($scope, 'create');
                $canSave = Gql::canSchema($scope, 'save');

                if ($canCreate || $canSave) {
                    // Create a mutation for each entry type
                    foreach ($field->getFieldLayoutProviders() as $provider) {
                        if ($provider instanceof EntryTypeModel) {
                            foreach (static::createSaveMutationsForField($field, $provider, $canSave) as $mutation) {
                                $mutationList[$mutation['name']] = $mutation;
                            }
                        }
                    }
                }

                if (!$createDraftMutations && $canSave) {
                    $createDraftMutations = true;
                }

                if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                    $createDeleteMutation = true;
                }
            }
        }

        if ($createDeleteMutation || $createDraftMutations) {
            $resolver = Craft::createObject(EntryMutationResolver::class);

            if ($createDeleteMutation) {
                $mutationList['deleteEntry'] = [
                    'name' => 'deleteEntry',
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                        'siteId' => Type::int(),
                    ],
                    'resolve' => [$resolver, 'deleteEntry'],
                    'description' => 'Delete an entry.',
                    'type' => Type::boolean(),
                ];
            }

            if ($createDraftMutations) {
                $mutationList['createDraft'] = [
                    'name' => 'createDraft',
                    'args' => [
                        'id' => [
                            'name' => 'id',
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'The id for the entry to create the draft for.',
                        ],
                        'name' => [
                            'name' => 'name',
                            'type' => Type::string(),
                            'description' => 'The name of the draft',
                        ],
                        'notes' => [
                            'name' => 'notes',
                            'type' => Type::string(),
                            'description' => 'Draft notes',
                        ],
                        'provisional' => [
                            'name' => 'provisional',
                            'type' => Type::boolean(),
                            'description' => 'Whether the draft should be a provisional draft.',
                        ],
                        'creatorId' => [
                            'name' => 'creatorId',
                            'type' => Type::int(),
                            'description' => 'The id of the creator of the draft.',
                        ],
                    ],
                    'resolve' => [$resolver, 'createDraft'],
                    'description' => 'Create a draft for an entry and return the draft ID.',
                    'type' => Type::id(),
                ];

                $mutationList['publishDraft'] = [
                    'name' => 'publishDraft',
                    'args' => [
                        'id' => [
                            'name' => 'id',
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'The id of the draft to be published.',
                        ],
                        'provisional' => [
                            'name' => 'provisional',
                            'type' => Type::boolean(),
                            'description' => 'Whether the draft is a provisional draft.',
                        ],
                    ],
                    'resolve' => [$resolver, 'publishDraft'],
                    'description' => 'Publish a draft for the entry and return the entry ID.',
                    'type' => Type::id(),
                ];
            }
        }

        return $mutationList;
    }

    /**
     * Create the per-entry-type save mutations.
     *
     * @param Section $section
     * @param EntryTypeModel $entryType
     * @param bool $createSaveDraftMutation
     * @return array
     * @throws InvalidConfigException
     */
    public static function createSaveMutations(
        Section $section,
        EntryTypeModel $entryType,
        bool $createSaveDraftMutation,
    ): array {
        // Don't use override data
        $entryType = $entryType->original ?? $entryType;

        $mutations = [];

        $entryMutationArguments = EntryMutationArguments::getArguments();
        $draftMutationArguments = DraftMutationArguments::getArguments();
        $generatedType = EntryType::generateType($entryType);

        /** @var EntryMutationResolver $resolver */
        $resolver = Craft::createObject(EntryMutationResolver::class);
        $resolver->setResolutionData('entryType', $entryType);
        $resolver->setResolutionData('section', $section);

        static::prepareResolver($resolver, $entryType->getCustomFields());

        switch ($section->type) {
            case Section::TYPE_SINGLE:
                $description = sprintf('Save the “%s” entry.', $section->name);
                $draftDescription = sprintf('Save the “%s” draft.', $section->name);

                unset($entryMutationArguments['authorId'], $entryMutationArguments['id'], $entryMutationArguments['uid']);
                unset($draftMutationArguments['authorId'], $draftMutationArguments['id'], $draftMutationArguments['uid']);
                break;
            case Section::TYPE_STRUCTURE:
                $entryMutationArguments = array_merge($entryMutationArguments, StructureArguments::getArguments());
            // no break
            default:
                $description = sprintf('Save a “%s” entry in the “%s” section.', $entryType->name, $section->name);
                $draftDescription = sprintf('Save a “%s” entry draft in the “%s” section.', $entryType->name, $section->name);
        }

        $contentFields = $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY);
        $entryMutationArguments = array_merge($entryMutationArguments, $contentFields);
        $draftMutationArguments = array_merge($draftMutationArguments, $contentFields);

        $mutations[] = [
            'name' => "save_{$section->handle}_{$entryType->handle}_Entry",
            'description' => $description,
            'args' => $entryMutationArguments,
            'resolve' => [$resolver, 'saveEntry'],
            'type' => $generatedType,
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => "save_{$section->handle}_{$entryType->handle}_Draft",
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => [$resolver, 'saveEntry'],
                'type' => $generatedType,
            ];
        }

        return $mutations;
    }

    /**
     * Create the per-entry-type save mutations for a nested entry field.
     *
     * @param ElementContainerFieldInterface $field
     * @param EntryTypeModel $entryType
     * @param bool $createSaveDraftMutation
     * @return array
     * @throws InvalidConfigException
     */
    public static function createSaveMutationsForField(
        ElementContainerFieldInterface $field,
        EntryTypeModel $entryType,
        bool $createSaveDraftMutation,
    ): array {
        // Don't use override data
        $entryType = $entryType->original ?? $entryType;

        $mutations = [];

        $entryMutationArguments = NestedEntry::getArguments();
        $draftMutationArguments = DraftMutationArguments::getArguments();
        $generatedType = EntryType::generateType($entryType);

        /** @var EntryMutationResolver $resolver */
        $resolver = Craft::createObject(EntryMutationResolver::class);
        $resolver->setResolutionData('entryType', $entryType);
        $resolver->setResolutionData('field', $field);

        static::prepareResolver($resolver, $entryType->getCustomFields());

        $description = sprintf('Save a “%s” entry in the “%s” %s field.', $entryType->name, $field->name, $field::displayName());
        $draftDescription = sprintf('Save a “%s” entry draft in the “%s” %s field.', $entryType->name, $field->name, $field::displayName());

        $contentFields = $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY);
        $entryMutationArguments = array_merge($entryMutationArguments, $contentFields);
        $draftMutationArguments = array_merge($draftMutationArguments, $contentFields);

        $mutations[] = [
            'name' => "save_{$field->handle}Field_{$entryType->handle}_Entry",
            'description' => $description,
            'args' => $entryMutationArguments,
            'resolve' => [$resolver, 'saveEntry'],
            'type' => $generatedType,
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => "save_{$field->handle}Field_{$entryType->handle}_Draft",
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => [$resolver, 'saveEntry'],
                'type' => $generatedType,
            ];
        }

        return $mutations;
    }
}
