<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use Craft;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\base\Query;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\types\elements\Entry as EntryGqlType;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\helpers\ArrayHelper;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType;
use craft\models\Section;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEntries()) {
            return [];
        }

        /** @var EntryGqlType[] $entryTypeGqlTypes */
        $entryTypeGqlTypes = array_map(
            fn(EntryType $entryType) => EntryTypeGenerator::generateType($entryType),
            ArrayHelper::index(
                GqlHelper::getSchemaContainedEntryTypes(),
                fn(EntryType $entryType) => $entryType->id
            ),
        );

        return [
            'entries' => [
                'type' => Type::listOf(EntryInterface::getType()),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class . '::resolve',
                'description' => 'This query is used to query for entries.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'entryCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of entries.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'entry' => [
                'type' => EntryInterface::getType(),
                'args' => [
                    ...EntryArguments::getArguments(),
                    ...EntryArguments::getContentArguments(),
                ],
                'resolve' => EntryResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single entry.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            ...self::sectionLevelFields($entryTypeGqlTypes),
            ...self::nestedEntryFieldLevelFields($entryTypeGqlTypes),
        ];
    }

    /**
     * Return the query fields for section level queries.
     *
     * @param EntryGqlType[] $entryTypeGqlTypes
     * @return array
     * @throws InvalidConfigException
     */
    private static function sectionLevelFields(array $entryTypeGqlTypes): array
    {
        $gqlTypes = [];
        $gqlService = Craft::$app->getGql();

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
                $arguments += $gqlService->getFieldLayoutArguments($entryType->getFieldLayout());
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
                'resolve' => fn($source, array $arguments, $context, ResolveInfo $resolveInfo) =>
                EntryResolver::resolve(null, $arguments + ['section' => $section->handle], $context, $resolveInfo),
            ];

            if ($section->type === Section::TYPE_SINGLE) {
                $name = "{$section->handle}Entry";
                $gqlTypes[$name] = [
                    'name' => $name,
                    'args' => $arguments,
                    'description' => sprintf('Single entry within the “%s” section.', $section->name),
                    'type' => $unionType,
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn($source, array $arguments, $context, ResolveInfo $resolveInfo) =>
                    EntryResolver::resolveOne(null, $arguments + ['section' => $section->handle], $context, $resolveInfo),
                ];
            }
        }

        return $gqlTypes;
    }

    /**
     * Return the query fields for nested entry field queries.
     *
     * @param EntryGqlType[] $entryTypeGqlTypes
     * @return array
     * @throws InvalidConfigException
     */
    private static function nestedEntryFieldLevelFields(array $entryTypeGqlTypes): array
    {
        $gqlTypes = [];
        $gqlService = Craft::$app->getGql();

        foreach (GqlHelper::getSchemaContainedNestedEntryFields() as $field) {
            $name = "{$field->handle}FieldEntries";
            $typeName = "{$field->handle}NestedEntriesQuery";
            $fieldQueryType = GqlEntityRegistry::getEntity($typeName);

            if (!$fieldQueryType) {
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
                    $arguments += $gqlService->getFieldLayoutArguments($entryType->getFieldLayout());
                }

                // Create the query field
                $fieldQueryType = [
                    'name' => $name,
                    'args' => $arguments,
                    'description' => sprintf('Entries within the “%s” %s field.', $field->name, $field::displayName()),
                    'type' => Type::listOf(GqlHelper::getUnionType("{$field->handle}FieldEntryUnion", $entryTypeGqlTypesInField)),
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn($source, array $arguments, $context, ResolveInfo $resolveInfo) =>
                    EntryResolver::resolve(null, $arguments + ['field' => $field->handle], $context, $resolveInfo),
                ];
            }

            $gqlTypes[$name] = $fieldQueryType;
        }

        return $gqlTypes;
    }
}
