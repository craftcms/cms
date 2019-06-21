<?php
namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\User as UserElement;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 */
class User extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        // If this is the begining of a resolver chain, start fresh
        if ($source === null) {
            $query = UserElement::find();
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

        if (!GqlHelper::canQueryUsers()) {
            return [];
        }

        $query->innerJoin(Table::USERGROUPS_USERS . ' usergroups_users',
            ['and',
                '[[users.id]] = [[usergroups_users.userId]]',
                ['in', '[[usergroups_users.groupId]]', array_values(Db::idsByUids(Table::USERGROUPS, $pairs['usergroups']))]
            ]
        );

        // todo might be a better way to do this.
        $query->groupBy = ['users.id'];

        return $query->all();
    }
}
