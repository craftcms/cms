<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Table;

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
        'fieldType' => Assets::class,
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField4',
        'fieldType' => Table::class,
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField5',
        'fieldType' => Entries::class,
    ],
    [
        'name' => 'Test field',
        'handle' => 'testField6',
        'fieldType' => PlainText::class,
    ],
];
