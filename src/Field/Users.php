<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Gql\Arguments\Elements\User as UserArguments;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\User as UserResolver;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * Users represents a Users field.
 */
class Users extends BaseRelationField
{
    #[Override]
    public static function displayName(): string
    {
        return t('Users');
    }

    #[Override]
    public static function icon(): string
    {
        return 'user-group';
    }

    public static function elementType(): string
    {
        return User::class;
    }

    #[Override]
    public static function defaultSelectionLabel(): string
    {
        return t('Add a user');
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', UserQuery::class, ElementCollection::class, User::class);
    }

    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryUsers($schema);
    }

    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(UserInterface::getType())),
            'args' => UserArguments::getArguments(),
            'resolve' => UserResolver::class.'::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    #[Override]
    public function getEagerLoadingGqlConditions(): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $userGroupUids = $allowedEntities['usergroups'] ?? [];

        if (in_array('everyone', $userGroupUids) || in_array('solo', $userGroupUids)) {
            return [];
        }

        if (empty($userGroupUids)) {
            return null;
        }

        $userGroupIds = array_filter(array_map(fn (string $uid) => UserGroups::getGroupByUid($uid)->id ?? null, $userGroupUids));

        return [
            'groupId' => $userGroupIds,
        ];
    }

    protected function createSelectionCondition(): ElementCondition
    {
        $condition = User::createCondition();
        $condition->queryParams = ['group', 'groupId'];

        return $condition;
    }
}
