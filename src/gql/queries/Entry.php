<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

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
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolve',
                'description' => 'This query is used to query for entries.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'entryCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => EntryArguments::getArguments(),
                'resolve' => EntryResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of entries.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'entry' => [
                'type' => EntryInterface::getType(),
                'args' => EntryArguments::getArguments(),
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

        foreach (GqlHelper::getSchemaContainedSections() as $section) {
            $typeName = "{$section->handle}SectionEntriesQuery";
            $sectionQueryType = GqlEntityRegistry::getEntity($typeName);

            if (!$sectionQueryType) {
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

                // Unset unusable arguments
                $arguments = EntryArguments::getArguments();
                unset(
                    $arguments['section'],
                    $arguments['sectionId'],
                    $arguments['field'],
                    $arguments['fieldId'],
                    $arguments['ownerId'],
                );

                // Create the section query field
                $sectionQueryType = [
                    'name' => "{$section->handle}Entries",
                    'args' => $arguments,
                    'description' => sprintf('Entries within the “%s” section.', $section->name),
                    'type' => Type::listOf(GqlHelper::getUnionType("{$section->handle}SectionEntryUnion", $entryTypesInSection)),
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn($source, array $arguments, $context, ResolveInfo $resolveInfo) =>
                        EntryResolver::resolve(null, $arguments + ['section' => $section->handle], $context, $resolveInfo),
                ];
            }

            $gqlTypes[$section->handle] = $sectionQueryType;
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

        foreach (GqlHelper::getSchemaContainedNestedEntryFields() as $field) {
            $typeName = "{$field->handle}NestedEntriesQuery";
            $fieldQueryType = GqlEntityRegistry::getEntity($typeName);

            if (!$fieldQueryType) {
                $entryTypesInField = [];

                // Loop through the entry types and create further queries
                foreach ($field->getFieldLayoutProviders() as $provider) {
                    if ($provider instanceof EntryType && isset($entryTypeGqlTypes[$provider->id])) {
                        $entryTypesInField[] = $entryTypeGqlTypes[$provider->id];
                    }
                }

                if (empty($entryTypesInField)) {
                    continue;
                }

                // Unset unusable arguments
                $arguments = EntryArguments::getArguments();
                unset(
                    $arguments['section'],
                    $arguments['sectionId'],
                    $arguments['field'],
                    $arguments['fieldId'],
                );

                // Create the query field
                $fieldQueryType = [
                    'name' => "{$field->handle}FieldEntries",
                    'args' => $arguments,
                    'description' => sprintf('Entries within the “%s” %s field.', $field->name, $field::displayName()),
                    'type' => Type::listOf(GqlHelper::getUnionType("{$field->handle}FieldEntryUnion", $entryTypesInField)),
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => fn($source, array $arguments, $context, ResolveInfo $resolveInfo) =>
                    EntryResolver::resolve(null, $arguments + ['field' => $field->handle], $context, $resolveInfo),
                ];
            }

            $gqlTypes[$field->handle] = $fieldQueryType;
        }

        return $gqlTypes;
    }
}
