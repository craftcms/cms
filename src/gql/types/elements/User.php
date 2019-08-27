<?php
namespace craft\gql\types\elements;

use craft\elements\User as UserElement;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\base\ObjectType;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\models\UserGroup;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 */
class User extends ObjectType
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

        if ($fieldName === 'preferences') {
            return Json::encode($source->getPreferences());
        }

        return $source->$fieldName;
    }

}
