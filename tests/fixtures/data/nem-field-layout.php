<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\elements\Entry;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Matrix;
use craft\fields\PlainText;

return [
    [
        'uid' => 'nem-field-layout-owner-----------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Content',
                'fields' => [
                    [
                        'uid' => 'nem-native-field-owner-----------uid',
                        'type' => EntryTitleField::class,
                        'required' => true,
                    ],
                    [
                        'uid' => 'nem-field-matrix-----------------uid',
                        'name' => 'NEM Matrix',
                        'handle' => 'nemMatrix',
                        'type' => Matrix::class,
                        'propagationMethod' => 'none',
                        'entryTypes' => [
                            'nem-entry-type-block-------------uid',
                        ],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],
    [
        'uid' => 'nem-field-layout-block-----------uid',
        'type' => Entry::class,
        'tabs' => [
            [
                'name' => 'Content',
                'fields' => [
                    [
                        'uid' => 'nem-field-text-------------------uid',
                        'name' => 'NEM Text',
                        'handle' => 'nemText',
                        'type' => PlainText::class,
                        'searchable' => true,
                        'required' => false,
                    ],
                    // A Matrix field nested inside a Matrix block, so 2-level-nested regression
                    // scenarios (e.g. #18461) can be reproduced.
                    [
                        'uid' => 'nem-field-inner-matrix-----------uid',
                        'name' => 'NEM Inner Matrix',
                        'handle' => 'nemInnerMatrix',
                        'type' => Matrix::class,
                        'propagationMethod' => 'none',
                        'entryTypes' => [
                            'nem-entry-type-block-------------uid',
                        ],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ],
];
