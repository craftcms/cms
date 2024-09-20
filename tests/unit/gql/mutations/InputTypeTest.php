<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use craft\base\Field;
use craft\fields\Checkboxes;
use craft\fields\Dropdown;
use craft\fields\Matrix as MatrixField;
use craft\fields\MultiSelect;
use craft\fields\RadioButtons;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\input\File;
use craft\gql\types\input\Matrix;
use craft\test\TestCase;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;

class InputTypeTest extends TestCase
{
    public function testFileInput(): void
    {
        self::assertInstanceOf(InputType::class, File::getType());
    }

    /**
     * Test allowed multiple options for field types.
     *
     * @param Field $field
     * @param bool $isMulti
     * @dataProvider multipleOptionsDataProvider
     */
    public function testMultipleOptions(Field $field, bool $isMulti): void
    {
        $type = $field->getContentGqlMutationArgumentType();

        if (is_array($type)) {
            $type = $type['type'];
        }

        while ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        if ($isMulti) {
            self::assertInstanceOf(ListOfType::class, $type);
        } else {
            self::assertNotInstanceOf(ListOfType::class, $type);
        }
    }

    /**
     *
     */
    public function testMatrixInput(): void
    {
        $matrixField = new MatrixField([
            'handle' => 'matrixField',
        ]);

        // Trigger addition to the registry
        Matrix::getType($matrixField);

        $fieldTypeName = $matrixField->handle . '_MatrixInput';
        self::assertNotFalse(GqlEntityRegistry::getEntity($fieldTypeName));
        self::assertNotEmpty(GqlEntityRegistry::getEntity($fieldTypeName)->getFields());
    }

    /**
     * Test Matrix input type normalizing values
     *
     * @dataProvider matrixInputValueNormalizerDataProvider
     * @param array $input
     * @param array $normalized
     */
    public function testMatrixInputValueNormalization(array $input, array $normalized): void
    {
        self::assertEquals($normalized, Matrix::normalizeValue($input));
    }

    public static function matrixInputValueNormalizerDataProvider(): array
    {
        return [
            [
                [
                    'entries' =>
                        [
                            ['blockType' => ['id' => 2, 'one', 'two']],
                            ['blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3], 'blockTypeB' => ['id' => 88, 'stuff' => 'ok']],
                        ],
                ],
                [
                    'entries' =>
                        [
                            2 => [
                                'type' => 'blockType',
                                'fields' => [
                                    'one',
                                    'two',
                                ],
                                'title' => null,
                            ],
                            'new:1' => [
                                'type' => 'blockTypeA',
                                'fields' => [
                                    'snap' => 1,
                                    'crackle' => 2,
                                    'pop' => 3,
                                ],
                                'title' => null,
                            ],
                        ],
                ],
            ],
            [
                [
                    'entries' =>
                        [
                            ['blockType' => ['id' => 2, 'one', 'two']],
                            ['blockTypeB' => ['id' => 88, 'stuff' => 'ok'], 'blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3]],
                        ],
                ],
                [
                    'entries' =>
                        [
                            2 => [
                                'type' => 'blockType',
                                'fields' => [
                                    'one',
                                    'two',
                                ],
                                'title' => null,
                            ],
                            88 => [
                                'type' => 'blockTypeB',
                                'fields' => [
                                    'stuff' => 'ok',
                                ],
                                'title' => null,
                            ],
                        ],
                ],
            ],
            [
                [
                    'entries' =>
                        [
                            ['blockType' => ['one']],
                            ['blockType' => ['two']],
                            ['blockType' => ['three']],
                            ['blockType' => ['four']],
                        ],
                ],
                [
                    'entries' =>
                        [
                            'new:1' => [
                                'type' => 'blockType',
                                'fields' => ['one'],
                                'title' => null,
                            ],
                            'new:2' => [
                                'type' => 'blockType',
                                'fields' => ['two'],
                                'title' => null,
                            ],
                            'new:3' => [
                                'type' => 'blockType',
                                'fields' => ['three'],
                                'title' => null,
                            ],
                            'new:4' => [
                                'type' => 'blockType',
                                'fields' => ['four'],
                                'title' => null,
                            ],
                        ],
                ],
            ],
        ];
    }

    public static function multipleOptionsDataProvider(): array
    {
        return [
            [new RadioButtons(['handle' => 'someField']), false],
            [new Dropdown(['handle' => 'someField']), false],
            [new Checkboxes(['handle' => 'someField']), true],
            [new MultiSelect(['handle' => 'someField']), true],
        ];
    }
}
