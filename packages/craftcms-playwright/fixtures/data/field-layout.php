<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Color;
use craft\fields\Matrix;
use craft\fields\Number;
use craft\fields\PlainText;

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
                        'uid' => 'native-field-1001----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // PLAIN TEXT FIELD
                    [
                        'uid' => 'field-1001-----------------------uid',
                        'name' => 'Plain Text Field',
                        'handle' => 'plainTextField',
                        'type' => PlainText::class,
                        'required' => false,
                    ],

                    // NUMBER FIELD
                    [
                        'uid' => 'field-1002-----------------------uid',
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
        'uid' => 'field-layout-1003----------------uid',
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
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1003-----------------------uid',
                        'name' => 'Matrix Cards Field',
                        'handle' => 'matrixCardsField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1004------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'field-layout-1004----------------uid',
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
                    // MATRIX FIELD IN ELEMENT INDEX MODE
                    [
                        'uid' => 'field-1004-----------------------uid',
                        'name' => 'Matrix Element Index Field',
                        'handle' => 'matrixElementIndexField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_INDEX,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1004------------------uid',
                            ],
                        ],
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
                    // Entry Title Field
                    [
                        'uid' => 'native-field-1004----------------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    // MATRIX FIELD IN BLOCKS MODE
                    [
                        'uid' => 'field-1005-----------------------uid',
                        'name' => 'Matrix Blocks Field',
                        'handle' => 'matrixBlocksField',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1004------------------uid',
                            ],
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
                    // PLAIN TEXT FIELD
                    [
                        'uid' => 'field-1006-----------------------uid',
                        'name' => 'Plain Text Field 2',
                        'handle' => 'plainTextField2',
                        'type' => PlainText::class,
                        'required' => false,
                    ],

                    // COLOUR
                    [
                        'uid' => 'field-1007-----------------------uid',
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
        'uid' => 'field-layout-1007----------------uid',
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
                    // COLOUR 2 FIELD
                    [
                        'uid' => 'field-1008-----------------------uid',
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
                        'uid' => 'field-1009-----------------------uid',
                        'name' => 'Matrix Blocks Field 2',
                        'handle' => 'matrixBlocksField2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1004------------------uid',
                            ],
                        ],
                    ],
                    // MATRIX FIELD IN CARDS MODE
                    [
                        'uid' => 'field-1010-----------------------uid',
                        'name' => 'Matrix Cards Field 2',
                        'handle' => 'matrixCardsField2',
                        'type' => Matrix::class,
                        'required' => false,
                        'viewMode' => Matrix::VIEW_MODE_CARDS,
                        'entryTypes' => [
                            [
                                'uid' => 'entry-type-1004------------------uid',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
