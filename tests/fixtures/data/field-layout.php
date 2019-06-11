<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\fields\Number;
use craft\fields\Matrix;
use craft\fields\PlainText;

return [
    [
        'type' => 'craft\test\Craft',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'name' => 'Test field',
                            'handle' => 'testField2',
                            'fieldType' => Number::class,
                        ]
                    ],
                ]
            ]
        ]
    ],
    [
        'type' => 'field_layout_with_matrix_and_normal_fields',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // MATRIX FIELD 1
                    [
                        'layout-link' => [
                            'required' => false
                        ],
                        'field' => [
                            'name' => 'Matrix 1',
                            'handle' => 'matrixFirst',
                            'fieldType' => Matrix::class,
                            'blockTypes' => [
                                'new1' => [
                                    'name' => 'A Block',
                                    'handle' => 'aBlock',
                                    'fields' => [
                                        'new1' => [
                                            'type' => PlainText::class,
                                            'name' => 'First Subfield',
                                            'handle' => 'firstSubfield',
                                            'instructions' => '',
                                            'required' => false,
                                            'typesettings' => [
                                                'multiline' => ''
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],

                    // PLAIN TEXT FIELD TWO
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'name' => 'Plain Text Field',
                            'handle' => 'plainTextField',
                            'fieldType' => Number::class,
                        ]
                    ],
                ]
            ]
        ]
    ]
];
