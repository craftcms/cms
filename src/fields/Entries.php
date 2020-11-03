<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\db\Table as DbTable;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Db;
use craft\helpers\Gql;
use GraphQL\Type\Definition\Type;

/**
 * Entries represents an Entries field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entries extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Entries');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return EntryQuery::class;
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(EntryInterface::getType()),
            'args' => EntryArguments::getArguments(),
            'resolve' => EntryResolver::class . '::resolve',
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions()
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $allowedSectionUids = $allowedEntities['sections'] ?? [];
        $allowedEntryTypeUids = $allowedEntities['entrytypes'] ?? [];

        if (empty($allowedSectionUids) || empty($allowedEntryTypeUids)) {
            return false;
        }

        $entryTypeIds = Db::idsByUids(DbTable::ENTRYTYPES, $allowedEntryTypeUids);
        $sectionIds = Db::idsByUids(DbTable::SECTIONS, $allowedSectionUids);

        return [
            'typeId' => array_values($entryTypeIds),
            'sectionId' => array_values($sectionIds)
        ];
    }
}
