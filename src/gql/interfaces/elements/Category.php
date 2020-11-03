<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Structure;
use craft\gql\TypeManager;
use craft\gql\types\generators\CategoryType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Category extends Structure
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return CategoryType::class;
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
            'description' => 'This is the interface implemented by all categories.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        CategoryType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'CategoryInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'groupId' => [
                'name' => 'groupId',
                'type' => Type::int(),
                'description' => 'The ID of the group that contains the category.'
            ],
            'groupHandle' => [
                'name' => 'groupHandle',
                'type' => Type::string(),
                'description' => 'The handle of the group that contains the category.'
            ],
            'children' => [
                'name' => 'children',
                'args' => CategoryArguments::getArguments(),
                'type' => Type::listOf(static::getType()),
                'description' => 'The category’s children.'
            ],
            'parent' => [
                'name' => 'parent',
                'args' => CategoryArguments::getArguments(),
                'type' => static::getType(),
                'description' => 'The category’s parent.'
            ],
            'url' => [
                'name' => 'url',
                'type' => Type::string(),
                'description' => 'The element’s full URL',
            ],
            'localized' => [
                'name' => 'localized',
                'args' => CategoryArguments::getArguments(),
                'type' => Type::listOf(static::getType()),
                'description' => 'The same element in other locales.',
            ],
            'prev' => [
                'name' => 'prev',
                'type' => self::getType(),
                'args' => CategoryArguments::getArguments(),
                'description' => 'Returns the previous element relative to this one, from a given set of criteria. CAUTION: Applying arguments to this field severely degrades the performance of the query.',
            ],
            'next' => [
                'name' => 'next',
                'type' => self::getType(),
                'args' => CategoryArguments::getArguments(),
                'description' => 'Returns the next element relative to this one, from a given set of criteria. CAUTION: Applying arguments to this field severely degrades the performance of the query.',
            ],
        ]), self::getName());
    }
}
