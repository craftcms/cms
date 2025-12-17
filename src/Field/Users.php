<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\elements\conditions\ElementCondition;
use craft\elements\db\UserQuery;
use craft\elements\ElementCollection;
use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\services\Gql as GqlService;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * Users represents a Users field.
 */
final class Users extends BaseRelationField
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Users');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function icon(): string
    {
        return 'user-group';
    }

    /**
     * {@inheritdoc}
     */
    public static function elementType(): string
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function defaultSelectionLabel(): string
    {
        return t('Add a user');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', UserQuery::class, ElementCollection::class, User::class);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryUsers($schema);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function createSelectionCondition(): ElementCondition
    {
        $condition = User::createCondition();
        $condition->queryParams = ['group', 'groupId'];

        return $condition;
    }
}
