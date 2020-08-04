<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\db\Table;
use craft\elements\User as UserElement;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends ElementResolver
{
    /**
     * @inheritdoc
     */
    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = UserElement::find();
            // If not, get the prepared element query
        } else {
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryUsers()) {
            return [];
        }

        if (!GqlHelper::canSchema('usergroups.everyone')) {
            $query->innerJoin(['usergroups_users' => Table::USERGROUPS_USERS],
                [
                    'and',
                    '[[users.id]] = [[usergroups_users.userId]]',
                    ['in', '[[usergroups_users.groupId]]', array_values(Db::idsByUids(Table::USERGROUPS, $pairs['usergroups']))]
                ]
            );

            // todo might be a better way to do this.
            $query->groupBy = ['users.id'];
        }

        return $query;
    }
}
