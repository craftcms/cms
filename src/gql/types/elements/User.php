<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\elements\User as UserElement;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\helpers\Gql;
use craft\helpers\Json;
use craft\models\UserGroup;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            UserInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
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
     * @param UserElement $user
     * @return UserGroup[]
     */
    private function resolveGroups(UserElement $user): array
    {
        $groups = $user->getGroups();

        if (Gql::canQueryAllUsers()) {
            return $groups;
        }

        return array_values(array_filter(
            $groups,
            fn(UserGroup $group) => Gql::canSchema("usergroups.$group->uid"),
        ));
    }
}
