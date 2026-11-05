<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use Craft;
use craft\gql\base\ObjectType;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;

/**
 * Class UserGroup
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class UserGroup extends ObjectType implements SingularTypeInterface
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'UserGroup';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self([
            'name' => self::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the type for user groups.',
        ]));
    }

    /**
     * Returns the type’s field definitions.
     *
     * @return array
     */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the user group.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the user group.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the user group.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the user group.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'The description of the user group.',
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
