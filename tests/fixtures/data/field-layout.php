<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Table;

return [
    [
        'uid' => 'field-layout-1000----------------uid',
        // Because User elements fetch layout by type
        'type' => User::class,
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
        'uid' => 'field-layout-1001----------------uid',
        'type' => Asset::class,
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
        'uid' => 'field-layout-1002----------------uid',
        'type' => Entry::class,
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

                    // PLAIN TEXT FIELD
                    [
                        'uid' => 'field-1001-----------------------uid',
                        'name' => 'Plain Text Field',
                        'handle' => 'plainTextField',
                        'type' => PlainText::class,
                        'required' => true,
                    ],

                    // TABLE FIELD
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
    [
        'uid' => 'field-layout-1003----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // MATRIX FIELD
                    [
                        'uid' => 'field-1003-----------------------uid',
                        'name' => 'Matrix 2',
                        'handle' => 'matrixSecond',
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
                            'new2' => [
                                'name' => 'B Block',
                                'handle' => 'bBlock',
                                'fields' => [
                                    'new1' => [
                                        'type' => Entries::class,
                                        'name' => 'Entries Subfield',
                                        'handle' => 'entriesSubfield',
                                        'required' => false,
                                    ],
                                ],
                            ],
                        ],
                        'required' => false,
                    ],

                    // PLAIN TEXT FIELD TWO
                    [
                        'uid' => 'field-1004-----------------------uid',
                        'name' => 'Plain Text Field2',
                        'handle' => 'plainTextField2',
                        'type' => PlainText::class,
                        'required' => false,
                    ],

                    // An entries field
                    [
                        'uid' => 'field-1005-----------------------uid',
                        'name' => 'Related Entry',
                        'handle' => 'relatedEntry',
                        'type' => Entries::class,
                        'sources' => [
                            'section:section-1000---------------------uid',
                        ],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1004----------------uid',
        'type' => GlobalSet::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // MATRIX FIELD 1
                    [
                        'uid' => 'field-1006-----------------------uid',
                        'name' => 'Matrix 1',
                        'handle' => 'matrixThird',
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

                    // PLAIN TEXT FIELD THREE
                    [
                        'uid' => 'field-1007-----------------------uid',
                        'name' => 'Plain Text Field3',
                        'handle' => 'plainTextField3',
                        'type' => PlainText::class,
                        'required' => true,
                    ],

                    // TABLE FIELD TWO
                    [
                        'uid' => 'field-1008-----------------------uid',
                        'name' => 'Appointments2',
                        'handle' => 'appointments2',
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
