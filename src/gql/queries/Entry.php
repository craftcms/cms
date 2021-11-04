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
use craft\gql\TypeManager;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

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

        $topLevelQueries = [
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
        ];
        $sectionLevelQueries = self::getSectionLevelFields();

        return array_merge($topLevelQueries, $sectionLevelQueries);
    }

    /**
     * Return the query fields for section level queries.
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected static function getSectionLevelFields(): array
    {
        $entryTypes = GqlHelper::getSchemaContainedEntryTypes();
        $entryTypeMap = [];

        foreach ($entryTypes as $entryType) {
            $entryTypeMap[$entryType->getSection()->handle][] = $entryType;
        }

        $gqlTypes = [];

        // For each `sectionHandle` => [EntryTypeModel]
        foreach ($entryTypeMap as $sectionHandle => $entryTypes) {
            $typeName = $sectionHandle . 'SectionEntriesQuery';

            // Unless we already have the type
            if (!($sectionQueryType = GqlEntityRegistry::getEntity($typeName))) {
                $entryTypeQueries = [];
                $entryTypesInSection = [];

                // Loop through the entry types and create further queries
                foreach ($entryTypes as $entryType) {
                    $entryTypeGqlType = EntryTypeGenerator::generateType($entryType);
                    $entryTypeQuery = self::getEntryTypeLevelFields($sectionHandle, $entryType, $entryTypeGqlType);
                    $entryTypeQueries[$entryType->handle] = $entryTypeQuery;
                    $entryTypesInSection[] = $entryTypeGqlType;
                }

                // Create a union for all entry types in this section
                $sectionEntryType = GqlHelper::getUnionType($sectionHandle . 'SectionEntryUnion', $entryTypesInSection);

                // Unset unusable arguments
                $arguments = EntryArguments::getArguments();
                unset($arguments['section'], $arguments['sectionId']);

                // Create custom inline resolvers that set the appropriate arguments and null the source, making sure
                // A new element query is created.
                $sectionQueryFields = [
                    'all' => [
                        'name' => 'all',
                        'args' => $arguments,
                        'description' => 'A list of entries within the ' . $sectionHandle . ' section.',
                        'complexity' => GqlHelper::singleQueryComplexity(),
                        'type' => Type::listOf($sectionEntryType),
                        'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($sectionHandle) {
                            $arguments['section'] = $sectionHandle;
                            return EntryResolver::resolve(null, $arguments, $context, $resolveInfo);
                        }
                    ],
                    'one' => [
                        'name' => 'one',
                        'args' => $arguments,
                        'description' => 'A single entry within the ' . $sectionHandle . ' section.',
                        'complexity' => GqlHelper::singleQueryComplexity(),
                        'type' => $sectionEntryType,
                        'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($sectionHandle) {
                            $arguments['section'] = $sectionHandle;
                            return EntryResolver::resolveOne(null, $arguments, $context, $resolveInfo);
                        }
                    ]
                ];

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $sectionQueryFields = array_merge($sectionQueryFields, $entryTypeQueries);

                // Create the section query field
                $sectionQueryType = [
                    'name' => $sectionHandle,
                    'description' => 'Entries within the ' . $sectionHandle . ' section.',
                    'type' => GqlEntityRegistry::createEntity($typeName, new ObjectType([
                        'name' => $typeName,
                        'fields' => fn() => TypeManager::prepareFieldDefinitions($sectionQueryFields, $typeName),
                    ])),
                    // Add a fake resolver just so the GQL parser believes there's a value further down the road.
                    'resolve' => fn() => []
                ];
            }

            $gqlTypes[$sectionHandle] = $sectionQueryType;
        }

        return $gqlTypes;
    }

    /**
     * Return the fields for entry type level queries.
     *
     * @param string $sectionHandle
     * @param EntryType $entryType
     * @param $createdGqlType
     * @return array|false|mixed
     */
    protected static function getEntryTypeLevelFields(string $sectionHandle, EntryType $entryType, $createdGqlType)
    {
        $typeName = $sectionHandle . '_' . $entryType->handle . 'EntriesQuery';

        if ($createdType = GqlEntityRegistry::getEntity($typeName)) {
            return $createdType;
        }

        // Unset unusable arguments
        $arguments = EntryArguments::getArguments();
        unset($arguments['type'], $arguments['typeId'], $arguments['section'], $arguments['sectionId']);

        // Create custom inline resolvers that set the appropriate arguments and null the source, making sure
        // A new element query is created.
        $entryTypeQueryFields = [
            'all' => [
                'name' => 'all',
                'args' => $arguments,
                'description' => 'A list of entries with the ' . $entryType->handle . ' entry type.',
                'complexity' => GqlHelper::singleQueryComplexity(),
                'type' => Type::listOf($createdGqlType),
                'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($entryType) {
                    $arguments['typeId'] = $entryType->id;
                    return EntryResolver::resolve(null, $arguments, $context, $resolveInfo);
                }
            ],
            'one' => [
                'name' => 'one',
                'args' => EntryArguments::getArguments(),
                'description' => 'A single entry with the ' . $sectionHandle . ' entry type.',
                'complexity' => GqlHelper::singleQueryComplexity(),
                'type' => $createdGqlType,
                'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($entryType) {
                    $arguments['typeId'] = $entryType->id;
                    return EntryResolver::resolveOne(null, $arguments, $context, $resolveInfo);
                }
            ]
        ];

        // Return the entry type field
        return [
            'name' => $entryType->handle,
            'description' => 'Entries with the ' . $entryType->handle . ' entry type.',
            'type' => GqlEntityRegistry::createEntity($typeName, new ObjectType([
                'name' => $typeName,
                'fields' => fn() => TypeManager::prepareFieldDefinitions($entryTypeQueryFields, $typeName)
            ])),
            // Add a fake resolver just so the GQL parser believes there's a value further down the road.
            'resolve' => fn() => []
        ];
    }
}
