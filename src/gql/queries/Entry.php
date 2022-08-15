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
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\helpers\Gql as GqlHelper;
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
     * @throws InvalidConfigException
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
                $entryTypesInSection = [];

                // Loop through the entry types and create further queries
                foreach ($entryTypes as $entryType) {
                    $entryTypeGqlType = EntryTypeGenerator::generateType($entryType);
                    $entryTypesInSection[] = $entryTypeGqlType;
                }

                // Unset unusable arguments
                $arguments = EntryArguments::getArguments();
                unset($arguments['section'], $arguments['sectionId']);

                // Create the section query field
                $sectionQueryType = [
                    'name' => $sectionHandle . 'Entries',
                    'args' => $arguments,
                    'description' => 'Entries within the ' . $sectionHandle . ' section.',
                    'type' => Type::listOf(GqlHelper::getUnionType($sectionHandle . 'SectionEntryUnion', $entryTypesInSection)),
                    // Enforce the section argument and set the source to `null`, to enforce a new element query.
                    'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) use ($sectionHandle) {
                        $arguments['section'] = $sectionHandle;
                        return EntryResolver::resolve(null, $arguments, $context, $resolveInfo);
                    },
                ];
            }

            $gqlTypes[$sectionHandle] = $sectionQueryType;
        }

        return $gqlTypes;
    }
}
