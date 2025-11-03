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
use craft\fieldlayoutelements\entries\EntryTitleField;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Table;

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
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1002----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD 1
                    [
                        'uid' => 'field-1000-----------------------uid',
                        'name' => 'Matrix 1',
                        'handle' => 'matrixFirst',
                        'type' => Matrix::class,
                        'entryTypes' => [
                            'entry-type-1007------------------uid',
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
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1003----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD 2
                    [
                        'uid' => 'field-1003-----------------------uid',
                        'name' => 'Matrix 2',
                        'handle' => 'matrixSecond',
                        'type' => Matrix::class,
                        'entryTypes' => [
                            'entry-type-1008------------------uid',
                            'entry-type-1009------------------uid',
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
                    // MATRIX FIELD 3
                    [
                        'uid' => 'field-1006-----------------------uid',
                        'name' => 'Matrix 3',
                        'handle' => 'matrixThird',
                        'type' => Matrix::class,
                        'entryTypes' => [
                            'entry-type-1010------------------uid',
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
    [
        'uid' => 'field-layout-1005----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'type' => PlainText::class,
                        'name' => 'First Subfield',
                        'handle' => 'firstSubfield',
                        'instructions' => '',
                        'required' => false,
                        'settings' => [
                            'multiline' => '',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1006----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'type' => PlainText::class,
                        'name' => 'Second Subfield',
                        'handle' => 'secondSubfield',
                        'instructions' => '',
                        'required' => false,
                        'settings' => [
                            'multiline' => '',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1007----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'type' => Entries::class,
                        'name' => 'Entries Subfield',
                        'handle' => 'entriesSubfield',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1008----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    [
                        'type' => PlainText::class,
                        'name' => 'Third Subfield',
                        'handle' => 'thirdSubfield',
                        'instructions' => '',
                        'required' => false,
                        'settings' => [
                            'multiline' => '',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1011----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1011----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],

                    // PLAIN TEXT FIELD FOUR
                    [
                        'uid' => 'field-1009-----------------------uid',
                        'name' => 'Plain Text Field4',
                        'handle' => 'plainTextField4',
                        'type' => PlainText::class,
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],

    // playwright
    [
        'uid' => 'field-layout-1012----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1001----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // PLAIN TEXT FIELD
                    [
                        'uid' => 'field-1010-----------------------uid',
                        'name' => 'Plain Text Field5',
                        'handle' => 'plainTextField5',
                        'type' => PlainText::class,
                        'required' => false,
                    ],

                    // NUMBER FIELD
                    [
                        'uid' => 'field-1011-----------------------uid',
                        'name' => 'Number Field',
                        'handle' => 'numberField',
                        'type' => Number::class,
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1013----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1004----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1012-----------------------uid',
                        'name' => 'Matrix Cards Field',
                        'handle' => 'matrixCardsField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1014----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1005----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN ELEMENT INDEX MODE
                    [
                        'uid' => 'field-1013-----------------------uid',
                        'name' => 'Matrix Element Index Field',
                        'handle' => 'matrixElementIndexField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_INDEX,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1015----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1006----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN BLOCKS MODE
                    [
                        'uid' => 'field-1014-----------------------uid',
                        'name' => 'Matrix Blocks Field',
                        'handle' => 'matrixBlocksField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1016----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // PLAIN TEXT FIELD
                    [
                        'uid' => 'field-1015-----------------------uid',
                        'name' => 'Plain Text Field6',
                        'handle' => 'plainTextField6',
                        'type' => PlainText::class,
                        'required' => false,
                    ],

                    // COLOUR
                    [
                        'uid' => 'field-1016-----------------------uid',
                        'name' => 'Colour',
                        'handle' => 'colour',
                        'type' => Color::class,
                        'required' => false,
                        'allowCustomColors' => true,
                        "palette" => [
                            [
                                "color" => "#ff00ff",
                                "label" => "pink",
                                "default" => "",
                            ],
                            [
                                "color" => "#bbff00",
                                "label" => "lime",
                                "default" => "",
                            ],
                            [
                                "color" => "#0099ff",
                                "label" => "",
                                "default" => "",
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1017----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1007----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // COLOUR 2 FIELD
                    [
                        'uid' => 'field-1017-----------------------uid',
                        'name' => 'Colour 2',
                        'handle' => 'colour2',
                        'type' => Color::class,
                        'required' => false,
                        'allowCustomColors' => true,
                        "palette" => [
                            [
                                "color" => "#ff00ff",
                                "label" => "pink",
                                "default" => "",
                            ],
                            [
                                "color" => "#bbff00",
                                "label" => "lime",
                                "default" => "",
                            ],
                            [
                                "color" => "#0099ff",
                                "label" => "",
                                "default" => "",
                            ],
                        ],
                    ],
                    // MATRIX FIELD IN BLOCKS MODE
                    [
                        'uid' => 'field-1018-----------------------uid',
                        'name' => 'Matrix Blocks Field 2',
                        'handle' => 'matrixBlocksField2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                    ],
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1019-----------------------uid',
                        'name' => 'Matrix Cards Field 2',
                        'handle' => 'matrixCardsField2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    // static matrix - min 1, max 1
    [
        'uid' => 'field-layout-1018----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1008----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1020-----------------------uid',
                        'name' => 'Static Matrix Cards Field',
                        'handle' => 'staticMatrixCardsField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'minEntries' => 1,
                        'maxEntries' => 1,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1019----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1009----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN ELEMENT INDEX MODE
                    [
                        'uid' => 'field-1021-----------------------uid',
                        'name' => 'Static Matrix Element Index Field',
                        'handle' => 'staticMatrixElementIndexField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_INDEX,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'minEntries' => 1,
                        'maxEntries' => 1,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1020----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1010----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN BLOCKS MODE
                    [
                        'uid' => 'field-1022-----------------------uid',
                        'name' => 'Static Matrix Blocks Field',
                        'handle' => 'staticMatrixBlocksField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'minEntries' => 1,
                        'maxEntries' => 1,
                    ],
                ],
            ],
        ],
    ],
    // matrix with max set - max 2
    [
        'uid' => 'field-layout-1021----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1011----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1023-----------------------uid',
                        'name' => 'Matrix Cards Field Max 2',
                        'handle' => 'matrixCardsFieldMax2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'maxEntries' => 2,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1022----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1012----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN ELEMENT INDEX MODE
                    [
                        'uid' => 'field-1024-----------------------uid',
                        'name' => 'Matrix Element Index Field Max 2',
                        'handle' => 'matrixElementIndexFieldMax2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_INDEX,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'maxEntries' => 2,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1023----------------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Tab 1',
                'fields' => [
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1013----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN BLOCKS MODE
                    [
                        'uid' => 'field-1025-----------------------uid',
                        'name' => 'Matrix Blocks Field Max 2',
                        'handle' => 'matrixBlocksFieldMax2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1016------------------uid',
                            ],
                        ],
                        'maxEntries' => 2,
                    ],
                ],
            ],
        ],
    ],
];
