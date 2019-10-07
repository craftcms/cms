<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces;

use craft\gql\TypeManager;
use GraphQL\Type\Definition\Type;

/**
 * Class Structure
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Structure extends Element
{
    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'lft' => [
                'name' => 'lft',
                'type' => Type::int(),
                'description' => 'The element’s left position within its structure.'
            ],
            'rgt' => [
                'name' => 'rgt',
                'type' => Type::int(),
                'description' => 'The element’s right position within its structure.'
            ],
            'level' => [
                'name' => 'level',
                'type' => Type::int(),
                'description' => 'The element’s level within its structure'
            ],
            'root' => [
                'name' => 'root',
                'type' => Type::int(),
                'description' => 'The element’s structure’s root ID'
            ],
            'structureId' => [
                'name' => 'structureId',
                'type' => Type::int(),
                'description' => 'The element’s structure ID.'
            ],
        ]), self::getName());
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'StructureInterface';
    }
}
