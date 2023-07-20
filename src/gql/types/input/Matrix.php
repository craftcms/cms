<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use Craft;
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
     * @param MatrixField $context
     * @return mixed
     */
    public static function getType(MatrixField $context): mixed
    {
        $typeName = $context->handle . '_MatrixInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($context) {
                // All the different field block types now get wrapped in a container input.
                // If two different block types are passed, the selected block type to parse is undefined.
                $blockTypeContainerName = $context->handle . '_MatrixBlockContainerInput';
                $blockContainerInputType = GqlEntityRegistry::getOrCreate($blockTypeContainerName, fn() => new InputObjectType([
                    'name' => $blockTypeContainerName,
                    'fields' => function() use ($context) {
                        $blockInputTypes = [];

                        foreach ($context->getBlockTypes() as $blockType) {
                            $blockTypeGqlName = $context->handle . '_' . $blockType->handle . '_MatrixBlockInput';
                            $blockInputTypes[$blockType->handle] = [
                                'name' => $blockType->handle,
                                'type' => GqlEntityRegistry::getOrCreate($blockTypeGqlName, fn() => new InputObjectType([
                                    'name' => $blockTypeGqlName,
                                    'fields' => function() use ($blockType) {
                                        $blockTypeFields = [
                                            'id' => [
                                                'name' => 'id',
                                                'type' => Type::id(),
                                            ],
                                        ];

                                        // Get the field input types
                                        foreach ($blockType->getCustomFields() as $field) {
                                            $blockTypeFields[$field->handle] = $field->getContentGqlMutationArgumentType();
                                        }

                                        return $blockTypeFields;
                                    },
                                ])),
                            ];
                        }

                        return $blockInputTypes;
                    },
                ]));

                return [
                    'sortOrder' => [
                        'name' => 'sortOrder',
                        'type' => Type::listOf(QueryArgument::getType()),
                    ],
                    'blocks' => [
                        'name' => 'blocks',
                        'type' => Type::listOf($blockContainerInputType),
                    ],
                ];
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize Matrix GraphQL input data to what Craft expects.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function normalizeValue(mixed $value): mixed
    {
        $preparedBlocks = [];
        $blockCounter = 1;
        $missingId = false;

        if (!empty($value['blocks'])) {
            foreach ($value['blocks'] as $block) {
                if (!empty($block)) {
                    $type = array_key_first($block);
                    $block = reset($block);
                    $missingId = $missingId || empty($block['id']);
                    $blockId = !empty($block['id']) ? $block['id'] : 'new:' . ($blockCounter++);

                    unset($block['id']);

                    $preparedBlocks[$blockId] = [
                        'type' => $type,
                        'fields' => $block,
                    ];
                }
            }

            if ($missingId) {
                Craft::$app->getDeprecator()->log('MatrixInput::normalizeValue()', 'The `id` field will be required when mutating Matrix fields as of Craft 4.0.');
            }

            $value['blocks'] = $preparedBlocks;
        }

        return $value;
    }
}
