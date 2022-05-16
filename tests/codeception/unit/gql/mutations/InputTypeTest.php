<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use craft\base\Field;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Checkboxes;
use craft\fields\Dropdown;
use craft\fields\Matrix as MatrixField;
use craft\fields\MultiSelect;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\input\File;
use craft\gql\types\input\Matrix;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\MatrixBlockType;
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
            $this->assertInstanceOf(ListOfType::class, $type);
        } else {
            $this->assertNotInstanceOf(ListOfType::class, $type);
        }
    }

    /**
     * @dataProvider testMatrixInputDataProvider
     * @param MatrixField $matrixField
     * @param MatrixBlockType[] $blockTypes
     */
    public function testMatrixInput(MatrixField $matrixField, array $blockTypes): void
    {
        // Trigger addition to the registry
        Matrix::getType($matrixField);

        $fieldTypeName = $matrixField->handle . '_MatrixInput';
        self::assertNotFalse(GqlEntityRegistry::getEntity($fieldTypeName));
        self::assertNotFalse(GqlEntityRegistry::getEntity($matrixField->handle . '_MatrixBlockContainerInput'));
        self::assertNotEmpty(GqlEntityRegistry::getEntity($fieldTypeName)->getFields());

        foreach ($blockTypes as $blockType) {
            self::assertNotFalse(GqlEntityRegistry::getEntity($matrixField->handle . '_' . $blockType->handle . '_MatrixBlockInput'));
        }
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

    public function testMatrixInputDataProvider(): array
    {
        $data = [];

        $matrixField = new MatrixField([
            'handle' => 'matrixField',
        ]);

        $blockTypes = [];

        for ($j = 0; $j < 3; $j++) {
            $blockType = new MatrixBlockType([
                'handle' => 'blockType' . ($j + 1),
            ]);

            $layoutElements = [];

            for ($k = 0; $k < 3; $k++) {
                $layoutElements[] = new CustomField(new PlainText([
                    'handle' => "nestedField$k",
                ]));
            }

            $fieldLayout = new FieldLayout();
            $tab = new FieldLayoutTab();
            $fieldLayout->setTabs([$tab]);
            $tab->setElements($layoutElements);
            $blockType->setFieldLayout($fieldLayout);

            $blockTypes[] = $blockType;
        }

        $matrixField->setBlockTypes($blockTypes);

        $data[] = [$matrixField, $blockTypes];

        return $data;
    }

    public function matrixInputValueNormalizerDataProvider(): array
    {
        return [
            [
                [
                    'blocks' =>
                        [
                            ['blockType' => ['id' => 2, 'one', 'two']],
                            ['blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3], 'blockTypeB' => ['id' => 88, 'stuff' => 'ok']],
                        ],
                ],
                [
                    'blocks' =>
                        [
                            2 => [
                                'type' => 'blockType',
                                'fields' => [
                                    'one',
                                    'two',
                                ],
                            ],
                            'new:1' => [
                                'type' => 'blockTypeA',
                                'fields' => [
                                    'snap' => 1,
                                    'crackle' => 2,
                                    'pop' => 3,
                                ],
                            ],
                        ],
                ],
            ],
            [
                [
                    'blocks' =>
                        [
                            ['blockType' => ['id' => 2, 'one', 'two']],
                            ['blockTypeB' => ['id' => 88, 'stuff' => 'ok'], 'blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3]],
                        ],
                ],
                [
                    'blocks' =>
                        [
                            2 => [
                                'type' => 'blockType',
                                'fields' => [
                                    'one',
                                    'two',
                                ],
                            ],
                            88 => [
                                'type' => 'blockTypeB',
                                'fields' => [
                                    'stuff' => 'ok',
                                ],
                            ],
                        ],
                ],
            ],
            [
                [
                    'blocks' =>
                        [
                            ['blockType' => ['one']],
                            ['blockType' => ['two']],
                            ['blockType' => ['three']],
                            ['blockType' => ['four']],
                        ],
                ],
                [
                    'blocks' =>
                        [
                            'new:1' => [
                                'type' => 'blockType',
                                'fields' => ['one'],
                            ],
                            'new:2' => [
                                'type' => 'blockType',
                                'fields' => ['two'],
                            ],
                            'new:3' => [
                                'type' => 'blockType',
                                'fields' => ['three'],
                            ],
                            'new:4' => [
                                'type' => 'blockType',
                                'fields' => ['four'],
                            ],
                        ],
                ],
            ],
        ];
    }

    public function multipleOptionsDataProvider(): array
    {
        return [
            [new RadioButtons(['handle' => 'someField']), false],
            [new Dropdown(['handle' => 'someField']), false],
            [new Checkboxes(['handle' => 'someField']), true],
            [new MultiSelect(['handle' => 'someField']), true],
        ];
    }
}
