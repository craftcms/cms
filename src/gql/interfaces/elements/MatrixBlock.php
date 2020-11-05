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
use craft\gql\types\generators\MatrixBlockType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlock extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return MatrixBlockType::class;
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
            'description' => 'This is the interface implemented by all matrix blocks.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        MatrixBlockType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'MatrixBlockInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::int(),
                'description' => 'The ID of the field that owns the matrix block.'
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'The ID of the element that owns the matrix block.'
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::int(),
                'description' => 'The ID of the matrix block\'s type.'
            ],
            'typeHandle' => [
                'name' => 'typeHandle',
                'type' => Type::string(),
                'description' => 'The handle of the matrix block\'s type.',
                'complexity' => Gql::singleQueryComplexity(),
            ],
            'sortOrder' => [
                'name' => 'sortOrder',
                'type' => Type::int(),
                'description' => 'The sort order of the matrix block within the owner element field.'
            ],
        ]), self::getName());
    }
}
