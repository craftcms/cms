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
                        'name' => 'Test field',
                        'handle' => 'testField2',
                        'type' => Number::class,
                        'required' => true,
                    ],
                ],
            ],
        ],
    ],
    [
        // Because User elements fetch layout by type
        'type' => 'craft\elements\User',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'name' => 'Short Biography',
                        'handle' => 'shortBio',
                        'type' => PlainText::class,
                        'required' => true,
                    ],
                ],
            ],
        ],
    ],
    [
        'type' => 'volume_field_layout',
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'name' => 'Image description',
                        'handle' => 'imageDescription',
                        'type' => PlainText::class,
                        'required' => true,
                    ],
                    [
                        'name' => 'Volume and mass',
                        'handle' => 'volumeAndMass',
                        'type' => PlainText::class,
                        'required' => true,
                    ],
                ],
            ],
        ],
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
                        'uid' => 'field-1000-----------------------uid',
                        'name' => 'Matrix 1',
                        'handle' => 'matrixFirst',
                        'type' => Matrix::class,
                        'blockTypes' => [
                            'new1' => [
                                'name' => 'A Block',
                                'handle' => 'aBlock',
                                'fields' => [
                                    'new1' => [
                                        'type' => PlainText::class,
                                        'name' => 'First Subfield',
                                        'handle' => 'firstSubfield',
                                        'columnSuffix' => 'aaaaaaaa',
                                        'instructions' => '',
                                        'required' => false,
                                        'typesettings' => [
                                            'multiline' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required' => false,
                    ],

                    // PLAIN TEXT FIELD TWO
                    [
                        'uid' => 'field-1001-----------------------uid',
                        'name' => 'Plain Text Field',
                        'handle' => 'plainTextField',
                        'type' => PlainText::class,
                        'required' => true,
                    ],

                    // A table field
                    [
                        'uid' => 'field-1002-----------------------uid',
                        'name' => 'Appointments',
                        'handle' => 'appointments',
                        'type' => Table::class,
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
                        'required' => true,
                    ],
                ],
            ],
        ],
    ],
];
