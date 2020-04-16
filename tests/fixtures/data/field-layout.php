<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Table;

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
        // Because User elements fetch layout by type
        'type' => 'craft\elements\User',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'name' => 'Short Biography',
                            'handle' => 'shortBio',
                            'fieldType' => PlainText::class,
                        ]
                    ],
                ]
            ]
        ]
    ],
    [
        'type' => 'volume_field_layout',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'name' => 'Image description',
                            'handle' => 'imageDescription',
                            'fieldType' => PlainText::class,
                        ]
                    ],
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'name' => 'Volume and mass',
                            'handle' => 'volumeAndMass',
                            'fieldType' => PlainText::class,
                        ]
                    ],
                ]
            ]
        ]
    ],
    [
        'uid' => 'field-layout-1000----------------uid',
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
                            'uid' => 'field-1000-----------------------uid',
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
                            'uid' => 'field-1001-----------------------uid',
                            'name' => 'Plain Text Field',
                            'handle' => 'plainTextField',
                            'fieldType' => PlainText::class,
                        ]
                    ],

                    // A table field
                    [
                        'layout-link' => [
                            'required' => true
                        ],
                        'field' => [
                            'uid' => 'field-1002-----------------------uid',
                            'name' => 'Appointments',
                            'handle' => 'appointments',
                            'fieldType' => Table::class,
                            'addRowLabel' => 'Add a row',
                            'minRows' => 1,
                            'maxRows' => 5,
                            'columns' => [
                                'col1' => [
                                    'heading' => 'What',
                                    'handle' => 'one',
                                    'type' => 'singleline',
                                ],
                                'col2' => [
                                    'heading' => 'When',
                                    'handle' => 'two',
                                    'type' => 'date',
                                ],
                                'col3' => [
                                    'heading' => 'How many',
                                    'handle' => 'howMany',
                                    'type' => 'number',
                                ],
                                'col4' => [
                                    'heading' => 'Allow?',
                                    'handle' => 'allow',
                                    'type' => 'lightswitch',
                                ],
                            ],
                        ]
                    ]
                ]
            ]
        ]
    ]
];
