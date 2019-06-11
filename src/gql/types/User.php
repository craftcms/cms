<?php
namespace craft\gql\types;

use craft\elements\User as UserElement;
use craft\gql\interfaces\elements\User as UserInterface;
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
        $config['interfaces'] = [UserInterface::getType()];
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var UserElement $source */
        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'groupHandles') {
            $groups = $source->getGroups();

            return array_map(function (UserGroup $userGroup) { return $userGroup->handle;}, $groups);
        }

        if ($fieldName === 'preferences') {
            return Json::encode($source->preferences);
        }

        return $source->$fieldName ?? null;
    }

}
