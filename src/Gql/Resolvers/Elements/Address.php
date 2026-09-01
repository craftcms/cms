<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Elements;

use BadMethodCallException;
use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementResolver;
use CraftCms\Cms\Support\Facades\UserGroups;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Address extends ElementResolver
{
    /** @param array<string, mixed> $arguments */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = AddressElement::find();
            $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');
            $conditions = [];

            if (isset($pairs['usergroups'])) {
                $groupIds = array_filter(array_map(
                    fn (string $uid) => UserGroups::getGroupByUid($uid)?->id,
                    $pairs['usergroups'],
                ));
                if (! empty($groupIds)) {
                    $conditions[] = function (Builder $query) use ($groupIds) {
                        $query->whereExists(
                            DB::table(Table::USERGROUPS_USERS, 'ugu')
                                ->whereColumn('ugu.userId', 'addresses.primaryOwnerId')
                                ->whereIn('ugu.groupId', $groupIds)
                        );
                    };
                }
            }

            if (empty($conditions)) {
                return ElementCollection::empty();
            }

            $query->where(function (Builder $query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere($condition);
                }
            });
        } else {
            // If not, get the prepared element query
            /** @var AddressQuery $query */
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (! $query instanceof ElementQueryInterface) {
            return $query;
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

        return $query;
    }
}
