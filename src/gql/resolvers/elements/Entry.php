<?php
namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\Entry as EntryElement;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
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

        $pairs = GqlHelper::extractAllowedEntitiesFromToken('read');

        if (!GqlHelper::canQueryEntries()) {
            return [];
        }

        $query->andWhere(['in', 'entries.sectionId', array_values(Db::idsByUids(Table::SECTIONS, $pairs['sections']))]);
        $query->andWhere(['in', 'entries.typeId', array_values(Db::idsByUids(Table::ENTRYTYPES, $pairs['entrytypes']))]);

        return $query->all();
    }
}
