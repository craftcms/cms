<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\User as UserElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\types\elements\User;

/**
 * Class UserType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class UserType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        // Users have no context
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        return GqlEntityRegistry::getOrCreate(UserElement::GQL_TYPE_NAME, fn() => new User([
            'name' => UserElement::GQL_TYPE_NAME,
            'fields' => function() use ($context) {
                // Users don't have different types, so the context for a user will be the same every time.
                $context ??= Craft::$app->getFields()->getLayoutByType(UserElement::class);
                $contentFieldGqlTypes = self::getContentFields($context);
                $userFields = array_merge(UserInterface::getFieldDefinitions(), $contentFieldGqlTypes);
                return Craft::$app->getGql()->prepareFieldDefinitions($userFields, UserElement::GQL_TYPE_NAME);
            },
        ]));
    }
}
