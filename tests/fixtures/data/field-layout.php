<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\fields\Number;

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
    ]
];
