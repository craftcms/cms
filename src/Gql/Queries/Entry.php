<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry as EntryResolver;
use CraftCms\Cms\Gql\Types\Elements\Entry as EntryGqlType;
use CraftCms\Cms\Gql\Types\Generators\EntryType as EntryTypeGenerator;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/** @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition */
class Entry extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && ! GqlHelper::canQueryEntries()) {
            return [];
        }

        /** @var EntryGqlType[] $entryTypeGqlTypes */
        $entryTypeGqlTypes = array_map(
            EntryTypeGenerator::generateType(...),
            Arr::keyBy(GqlHelper::getSchemaContainedEntryTypes(), 'id'),
        );

        return [
            'entries' => [
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class.'::resolve',
                'description' => 'This query is used to query for entries.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'entryCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class.'::resolveCount',
                'description' => 'This query is used to return the number of entries.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'entry' => [
                'type' => EntryInterface::getType(),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class.'::resolveOne',
                'description' => 'This query is used to query for a single entry.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            ...self::sectionLevelFields($entryTypeGqlTypes),
            ...self::nestedEntryFieldLevelFields($entryTypeGqlTypes),
        ];
    }

    /**
     * @param  EntryGqlType[]  $entryTypeGqlTypes
     * @return array<string, UnnamedFieldDefinitionConfig>
     */
    private static function sectionLevelFields(array $entryTypeGqlTypes): array
    {
        $gqlTypes = [];

        foreach (GqlHelper::getSchemaContainedSections() as $section) {
            $entryTypesInSection = [];

            // Loop through the entry types and create further queries
            foreach ($section->getEntryTypes() as $entryType) {
                if (isset($entryTypeGqlTypes[$entryType->id])) {
                    $entryTypesInSection[] = $entryTypeGqlTypes[$entryType->id];
                }
            }

            if (empty($entryTypesInSection)) {
                continue;
            }

            $arguments = EntryArguments::getArguments();

            // Unset unusable arguments
            unset(
                $arguments['section'],
                $arguments['sectionId'],
                $arguments['field'],
                $arguments['fieldId'],
                $arguments['ownerId'],
            );

            foreach ($section->getEntryTypes() as $entryType) {
                $arguments += Gql::getFieldLayoutArguments($entryType->getFieldLayout());
            }

            $unionType = GqlHelper::getUnionType("{$section->handle}SectionEntryUnion", $entryTypesInSection);

            // Create the section query field
            $name = "{$section->handle}Entries";
            $gqlTypes[$name] = [
                'name' => $name,
                'args' => $arguments,
                'description' => sprintf('Entries within the “%s” section.', $section->name),
                'type' => Type::listOf($unionType),
                // Enforce the section argument and set the source to `null`, to enforce a new element query.
                'resolve' => fn ($source, array $arguments, $context, ResolveInfo $resolveInfo) => EntryResolver::resolve(null, $arguments + ['section' => $section->handle], $context, $resolveInfo),
            ];

            if ($section->type === SectionType::Single) {
                $name = "{$section->handle}Entry";
                $gqlTypes[$name] = [
                    'name' => $name,
                    'args' => $arguments,
                    'description' => sprintf('Single entry within the “%s” section.', $section->name),
                    'type' => $unionType,
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn ($source, array $arguments, $context, ResolveInfo $resolveInfo) => EntryResolver::resolveOne(null, $arguments + ['section' => $section->handle], $context, $resolveInfo),
                ];
            }
        }

        return $gqlTypes;
    }

    /**
     * @param  EntryGqlType[]  $entryTypeGqlTypes
     * @return array<string, UnnamedFieldDefinitionConfig>
     */
    private static function nestedEntryFieldLevelFields(array $entryTypeGqlTypes): array
    {
        $gqlTypes = [];

        foreach (GqlHelper::getSchemaContainedNestedEntryFields() as $field) {
            $name = "{$field->handle}FieldEntries";
            $typeName = "{$field->handle}NestedEntriesQuery";
            $fieldQueryType = GqlEntityRegistry::getEntity($typeName);

            if (! $fieldQueryType) {
                $entryTypesInField = [];
                $entryTypeGqlTypesInField = [];

                // Loop through the entry types and create further queries
                foreach ($field->getFieldLayoutProviders() as $provider) {
                    if ($provider instanceof EntryType && isset($entryTypeGqlTypes[$provider->id])) {
                        $entryTypesInField[] = $provider;
                        $entryTypeGqlTypesInField[] = $entryTypeGqlTypes[$provider->id];
                    }
                }

                if (empty($entryTypeGqlTypesInField)) {
                    continue;
                }

                $arguments = EntryArguments::getArguments();

                // Unset unusable arguments
                unset(
                    $arguments['section'],
                    $arguments['sectionId'],
                    $arguments['field'],
                    $arguments['fieldId'],
                );

                foreach ($entryTypesInField as $entryType) {
                    $arguments += Gql::getFieldLayoutArguments($entryType->getFieldLayout());
                }

                // Create the query field
                $fieldQueryType = [
                    'name' => $name,
                    'args' => $arguments,
                    'description' => sprintf('Entries within the “%s” %s field.', $field->name, $field::displayName()),
                    'type' => Type::listOf(GqlHelper::getUnionType("{$field->handle}FieldEntryUnion", $entryTypeGqlTypesInField)),
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn ($source, array $arguments, $context, ResolveInfo $resolveInfo) => EntryResolver::resolve(null, $arguments + ['field' => $field->handle], $context, $resolveInfo),
                ];
            }

            $gqlTypes[$name] = $fieldQueryType;
        }

        return $gqlTypes;
    }
}
