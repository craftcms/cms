<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use craft\base\Field;
use craft\fields\Matrix as MatrixField;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Matrix
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Matrix extends InputObjectType
{
    /**
     * Create the type for a matrix field.
     *
     * @param $context
     * @return bool|mixed
     */
    public static function getType(MatrixField $context)
    {
        /** @var MatrixField $context */
        $typeName = $context->handle . '_MatrixInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        // Array of block types.
        $blockTypes = $context->getBlockTypes();
        $blockInputTypes = [];

        // For all the blocktypes
        foreach ($blockTypes as $blockType) {
            $fields = $blockType->getFields();
            $blockTypeFields = [];

            // Get the field input types
            foreach ($fields as $field) {
                /** @var Field $field */
                $blockTypeFields[$field->handle] = $field->getContentGqlMutationArgumentType();
            }

            $blockTypeGqlName = $context->handle . '_' . $blockType->handle . '_MatrixBlockInput';
            $blockInputTypes[$blockType->handle] = [
                'name' => $blockType->handle,
                'type' => GqlEntityRegistry::createEntity($blockTypeGqlName, new InputObjectType([
                    'name' => $blockTypeGqlName,
                    'fields' => $blockTypeFields
                ]))
            ];
        }

        // All the different field block types now get wrapped in a container input.
        // If two different block types are passed, the selected block type to parse is undefined.
        $blockTypeContainerName = $context->handle . '_MatrixBlockContainerInput';
        $blockContainerInputType = GqlEntityRegistry::createEntity($blockTypeContainerName, new InputObjectType([
            'name' => $blockTypeContainerName,
            'fields' => function() use ($blockInputTypes) {
                return $blockInputTypes;
            }
        ]));

        $inputType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($blockContainerInputType) {
                return [
                    'sortOrder' => [
                        'name' => 'sortOrder',
                        'type' => Type::nonNull(Type::listOf(QueryArgument::getType()))
                    ],
                    'blocks' => [
                        'name' => 'blocks',
                        'type' => Type::listOf($blockContainerInputType)
                    ]
                ];
            },
            'normalizeValue' => [self::class, 'normalizeValue']
        ]));

        return $inputType;
    }

    /**
     * Normalize Matrix GraphQL input data to what Craft expects.
     *
     * @param $value
     * @return mixed
     */
    public static function normalizeValue($value)
    {
        $preparedBlocks = [];
        $blockCounter = 1;

        if (!empty($value['blocks'])) {
            foreach ($value['blocks'] as $block) {
                if (!empty($block)) {
                    $type = array_key_first($block);
                    $block = reset($block);
                    $blockId = !empty($block['id']) ? $block['id'] : 'new:' . ($blockCounter++);
                    $preparedBlocks[$blockId] = [
                        'type' => $type,
                        'fields' => $block
                    ];
                }
            }

            $value['blocks'] = $preparedBlocks;
        }

        return $value;
    }
}
