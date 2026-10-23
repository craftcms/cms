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
 * Class TagGroup
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 * @deprecated in 6.0.0
 */
class TagGroup extends ObjectType implements SingularTypeInterface
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'TagGroup';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self([
            'name' => self::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the type for tag groups.',
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
                'description' => 'The ID of the tag group.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the tag group.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the tag group.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the tag group.',
            ],
        ];

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
