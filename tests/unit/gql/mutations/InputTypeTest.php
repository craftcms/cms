<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Test\Unit;
use craft\fields\Matrix as MatrixField;
use craft\fields\PlainText;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\input\File;
use craft\gql\types\input\Matrix;
use craft\models\MatrixBlockType;
use GraphQL\Type\Definition\InputType;

class InputTypeTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testFileInput()
    {
        $this->assertInstanceOf(InputType::class, File::getType());
    }

    /**
     * @dataProvider testMatrixInputDataProvider
     */
    public function testMatrixInput($matrixField, $blockTypes)
    {
        $inputType = Matrix::getType($matrixField);

        $fieldTypeName = $matrixField->handle . '_MatrixInput';
        $this->assertNotFalse(GqlEntityRegistry::getEntity($fieldTypeName));
        $this->assertNotFalse(GqlEntityRegistry::getEntity($matrixField->handle . '_MatrixBlockContainerInput'));
        $this->assertNotEmpty(GqlEntityRegistry::getEntity($fieldTypeName)->getFields());

        foreach ($blockTypes as $blockType) {
            $this->assertNotFalse(GqlEntityRegistry::getEntity($matrixField->handle . '_' . $blockType->handle . '_MatrixBlockInput'));
        }
    }

    /**
     * Test Matrix input type normalizing values
     *
     * @dataProvider matrixInputValueNormalizerDataProvicer
     */
    public function testMatrixInputValueNormalization($input, $normalized)
    {
        $this->assertEquals($normalized, Matrix::normalizeValue($input));
    }

    public function testMatrixInputDataProvider()
    {
        $data = [];

        $matrixField = new MatrixField([
            'handle' => 'matrixField'
        ]);

        $blockTypes = [];

        for ($j = 0; $j < 3; $j++) {
            $blockType = new MatrixBlockType([
                'handle' => 'blockType' . ($j + 1)
            ]);

            $fields = [];

            for ($k = 0; $k < 3; $k++) {
                $fields[] = new PlainText([
                    'handle' => 'nestedField' . $k
                ]);
            }

            $blockType->setFields($fields);
            $blockTypes[] = $blockType;
        }

        $matrixField->setBlockTypes($blockTypes);

        $data[] = [$matrixField, $blockTypes];

        return $data;
    }

    public function matrixInputValueNormalizerDataProvicer()
    {
        return [
            [
                ['blocks' =>
                    [
                        ['blockType' => ['id' => 2, 'one', 'two']],
                        ['blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3], 'blockTypeB' => ['id' => 88, 'stuff' => 'ok']]
                    ]
                ],
                ['blocks' =>
                    [
                        2 => [
                            'type' => 'blockType',
                            'fields' => [
                                'id' => 2,
                                'one',
                                'two'
                            ]
                        ],
                        'new:1' => [
                            'type' => 'blockTypeA',
                            'fields' => [
                                'snap' => 1,
                                'crackle' => 2,
                                'pop' => 3
                            ]
                        ]
                    ]
                ],
            ],
            [
                ['blocks' =>
                    [
                        ['blockType' => ['id' => 2, 'one', 'two']],
                        ['blockTypeB' => ['id' => 88, 'stuff' => 'ok'], 'blockTypeA' => ['snap' => 1, 'crackle' => 2, 'pop' => 3]]
                    ]
                ],
                ['blocks' =>
                    [
                        2 => [
                            'type' => 'blockType',
                            'fields' => [
                                'id' => 2,
                                'one',
                                'two'
                            ]
                        ],
                        88 => [
                            'type' => 'blockTypeB',
                            'fields' => [
                                'id' => 88,
                                'stuff' => 'ok',
                            ]
                        ]
                    ]
                ]
            ],
            [
                ['blocks' =>
                    [
                        ['blockType' => ['one']],
                        ['blockType' => ['two']],
                        ['blockType' => ['three']],
                        ['blockType' => ['four']],
                    ]
                ],
                ['blocks' =>
                    [
                        'new:1' => [
                            'type' => 'blockType',
                            'fields' => ['one']
                        ],
                        'new:2' => [
                            'type' => 'blockType',
                            'fields' => ['two']
                        ],
                        'new:3' => [
                            'type' => 'blockType',
                            'fields' => ['three']
                        ],
                        'new:4' => [
                            'type' => 'blockType',
                            'fields' => ['four']
                        ]
                    ]
                ]
            ]
        ];
    }
}
