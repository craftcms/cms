<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use craft\gql\types\generators\TagType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Tag extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return TagType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all tags.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        TagType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'TagInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        // @TODO Remove the `uri` field for Assets.
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::int(),
                'description' => 'The ID of the group that contains the tag.'
            ],
            'groupHandle' => [
                'name' => 'groupHandle',
                'type' => Type::string(),
                'description' => 'The handle of the group that contains the tag.',
                'complexity' => Gql::singleQueryComplexity(),
            ]
        ]), self::getName());
    }
}
