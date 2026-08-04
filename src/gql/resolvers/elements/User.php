<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\ElementCollection;
use craft\elements\User as UserElement;
use craft\enums\CmsEdition;
use craft\gql\base\ElementResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Gql as GqlHelper;
use yii\base\UnknownMethodException;

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
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = UserElement::find();
        } else {
            // If not, get the prepared element query
            /** @var UserQuery $query */
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
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

            $userGroupsService = Craft::$app->getUserGroups();
            if (Craft::$app->edition < CmsEdition::Pro) {
                $availableGroupUids = array_map(fn($group) => $group->uid, $userGroupsService->getAllGroups());
                $pairs['usergroups'] = array_filter($pairs['usergroups'], fn($uid) => in_array($uid, $availableGroupUids));
            }
            $allowedGroupIds = array_filter(array_map(function(string $uid) use ($userGroupsService) {
                $userGroupsService = $userGroupsService->getGroupByUid($uid);
                return $userGroupsService->id ?? null;
            }, $pairs['usergroups']));

            $query->groupId = $query->groupId ? array_intersect($allowedGroupIds, (array)$query->groupId) : $allowedGroupIds;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (UnknownMethodException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        if (!GqlHelper::canQueryUsers()) {
            return ElementCollection::empty();
        }

        return $query;
    }
}
