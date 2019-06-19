<?php
namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Entry
 */
class Entry extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = EntryElement::find();
        // If not, get the prepared element query
        } else {
            $fieldName = $resolveInfo->fieldName;
            $query = $source->$fieldName;
        }

        $arguments = self::prepareArguments($arguments);

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = Gql::extractAllowedEntitiesFromToken('read');

        if (!empty($pairs['sections'])) {
            $allowedIds = Db::idsByUids(Table::SECTIONS, $pairs['sections']);
            $query->sectionId = $query->sectionId ? array_intersect($allowedIds, (array)$query->sectionId) : $allowedIds;
        } else {
            return [];
        }

        if (!empty($pairs['entrytypes'])) {
            $allowedIds = Db::idsByUids(Table::ENTRYTYPES, $pairs['entrytypes']);
            $query->typeId = $query->typeId ? array_intersect($allowedIds, (array)$query->typeId) : $allowedIds;
        } else {
            return [];
        }

        return $query->all();
    }
}
