<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User as UserElement;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class User extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            UserInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var UserElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'preferences' => Json::encode($source->getPreferences()),
            'affiliatedSiteHandle' => $source->getAffiliatedSite()?->handle,
            'groups' => $this->resolveGroups($source),
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }

    /**
     * Returns the user’s groups that the active schema is allowed to see.
     *
     * Unless the schema can query all users, only groups that the schema can query users in are returned, so that
     * membership in other groups isn’t exposed.
     *
     * @return UserGroup[]
     */
    private function resolveGroups(UserElement $user): array
    {
        $groups = $user->getGroups();

        if (GqlHelper::canQueryAllUsers()) {
            return $groups;
        }

        return array_values(array_filter(
            $groups,
            fn (UserGroup $group) => GqlHelper::canSchema("usergroups.$group->uid"),
        ));
    }
}
