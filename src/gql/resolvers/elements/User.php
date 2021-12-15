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
use craft\helpers\ArrayHelper;
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

        if (!GqlHelper::canSchema('usergroups.everyone')) {
            $groups = ArrayHelper::remove($arguments, 'group');
            if ($groups) {
                $query->group($groups);
            }

            $groupIds = ArrayHelper::remove($arguments, 'groupId');
            if ($groupIds) {
                $query->groupId($groupIds);
            }

            $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');
            $allowedGroupIds = array_values(Db::idsByUids(Table::USERGROUPS, $pairs['usergroups']));

            $query->groupId = $query->groupId ? array_intersect($allowedGroupIds, (array)$query->groupId) : $allowedGroupIds;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        if (!GqlHelper::canQueryUsers()) {
            return [];
        }

        return $query;
    }
}
