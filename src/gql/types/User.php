<?php
namespace craft\gql\types;

use craft\elements\User as UserElement;
use craft\gql\interfaces\elements\Element as ElementInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\models\UserGroup;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 */
class User extends BaseType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            UserInterface::getType(),
            ElementInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var UserElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'groupHandles') {
            $groups = $source->getGroups();

            $pairs = GqlHelper::extractAllowedEntitiesFromToken('read');

            if (empty($pairs['usergroups'])) {
                return [];
            }

            $allowedGroups = array_flip($pairs['usergroups']);

            // Don't list the groups user has no access to see.
            $groups = array_filter($groups, function (UserGroup $userGroup) use ($allowedGroups) {
                return isset($allowedGroups[$userGroup->uid]);
            });

            return array_map(function (UserGroup $userGroup) { return $userGroup->handle;}, $groups);
        }

        if ($fieldName === 'preferences') {
            return Json::encode($source->preferences);
        }

        return $source->$fieldName;
    }

}
