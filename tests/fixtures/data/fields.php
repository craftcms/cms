<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\fields\Assets;
use craft\fields\Entries;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Table;

return [
    [
        'name' => 'Test field',
        'handle' => 'testField',
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField2',
        'fieldType' => Number::class,
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField3',
        'fieldType' => Assets::class
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField4',
        'fieldType' => Table::class
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField5',
        'fieldType' => Entries::class
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField6',
        'fieldType' => PlainText::class
    ]
];