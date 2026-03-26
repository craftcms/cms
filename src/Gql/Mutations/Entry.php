<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Mutations;

use Craft;
use CraftCms\Cms\Entry\Data\EntryType as EntryTypeData;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Gql\Arguments\Mutations\Draft as DraftMutationArguments;
use CraftCms\Cms\Gql\Arguments\Mutations\Entry as EntryMutationArguments;
use CraftCms\Cms\Gql\Arguments\Mutations\NestedEntry;
use CraftCms\Cms\Gql\Arguments\Mutations\Structure as StructureArguments;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\Mutations\Entry as EntryMutationResolver;
use CraftCms\Cms\Gql\Types\Generators\EntryType;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use GraphQL\Type\Definition\Type;

class Entry extends Mutation
{
    public static function getMutations(): array
    {
        $mutationList = [];
        $createDeleteMutation = false;
        $createDraftMutations = false;

        foreach (Sections::getAllSections() as $section) {
            $scope = "sections.$section->uid";
            $isSingle = $section->type === SectionType::Single;
            $canCreate = ! $isSingle && Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');

            if ($canCreate || $canSave) {
                // Create a mutation for each entry type
                foreach ($section->getEntryTypes() as $entryType) {
                    foreach (static::createSaveMutations($section, $entryType, $canSave) as $mutation) {
                        $mutationList[$mutation['name']] = $mutation;
                    }
                }
            }

            if (! $createDraftMutations && $canSave) {
                $createDraftMutations = true;
            }

            if (! $createDeleteMutation && ! $isSingle && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        foreach (Fields::getNestedEntryFieldTypes() as $type) {
            foreach (Fields::getFieldsByType($type) as $field) {
                /** @var ElementContainerFieldInterface $field */
                $scope = "nestedentryfields.$field->uid";
                $canCreate = Gql::canSchema($scope, 'create');
                $canSave = Gql::canSchema($scope, 'save');

                if ($canCreate || $canSave) {
                    // Create a mutation for each entry type
                    foreach ($field->getFieldLayoutProviders() as $provider) {
                        if ($provider instanceof EntryTypeData) {
                            foreach (static::createSaveMutationsForField($field, $provider, $canSave) as $mutation) {
                                $mutationList[$mutation['name']] = $mutation;
                            }
                        }
                    }
                }

                if (! $createDraftMutations && $canSave) {
                    $createDraftMutations = true;
                }

                if (! $createDeleteMutation && Gql::canSchema($scope, 'delete')) {
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

    public static function createSaveMutations(
        Section $section,
        EntryTypeData $entryType,
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
            case SectionType::Single:
                $description = sprintf('Save the “%s” entry.', $section->name);
                $draftDescription = sprintf('Save the “%s” draft.', $section->name);

                unset($entryMutationArguments['authorId'], $entryMutationArguments['id'], $entryMutationArguments['uid']);
                unset($draftMutationArguments['authorId'], $draftMutationArguments['id'], $draftMutationArguments['uid']);
                break;
            case SectionType::Structure:
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
            'resolve' => $resolver->saveEntry(...),
            'type' => $generatedType,
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => "save_{$section->handle}_{$entryType->handle}_Draft",
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => $resolver->saveEntry(...),
                'type' => $generatedType,
            ];
        }

        return $mutations;
    }

    /**
     * Create the per-entry-type save mutations for a nested entry field.
     */
    public static function createSaveMutationsForField(
        ElementContainerFieldInterface $field,
        EntryTypeData $entryType,
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
            'resolve' => $resolver->saveEntry(...),
            'type' => $generatedType,
        ];

        // This gets created only if allowed to save entries
        if ($createSaveDraftMutation) {
            $mutations[] = [
                'name' => "save_{$field->handle}Field_{$entryType->handle}_Draft",
                'description' => $draftDescription,
                'args' => $draftMutationArguments,
                'resolve' => $resolver->saveEntry(...),
                'type' => $generatedType,
            ];
        }

        return $mutations;
    }
}
