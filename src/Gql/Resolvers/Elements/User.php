<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Elements;

use BadMethodCallException;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementResolver;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User as UserElement;

class User extends ElementResolver
{
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
        if (! $query instanceof ElementQueryInterface) {
            return $query;
        }

        if (! GqlHelper::canSchema('usergroups.everyone')) {
            if ($groups = Arr::pull($arguments, 'group')) {
                $query->group($groups);
            }

            if ($groupIds = Arr::pull($arguments, 'groupId')) {
                $query->groupId($groupIds);
            }

            $pairs = GqlHelper::extractAllowedEntitiesFromSchema();

            if (Edition::get() < Edition::Pro) {
                $availableGroupUids = UserGroups::getAllGroups()->pluck('uid')->all();
                $pairs['usergroups'] = array_filter($pairs['usergroups'], fn ($uid) => in_array($uid, $availableGroupUids));
            }

            $allowedGroupIds = array_filter(array_map(fn (string $uid) => UserGroups::getGroupByUid($uid)->id ?? null, $pairs['usergroups']));

            $query->groupId = $query->groupId ? array_intersect($allowedGroupIds, (array) $query->groupId) : $allowedGroupIds;
        }

        foreach ($arguments as $key => $value) {
            try {
                $query->$key($value);
            } catch (BadMethodCallException $e) {
                if ($value !== null) {
                    throw $e;
                }
            }
        }

        if (! GqlHelper::canQueryUsers()) {
            return ElementCollection::empty();
        }

        return $query;
    }
}
