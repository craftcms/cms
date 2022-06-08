<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    'basic-volume' => [
        'id' => '1000',
        'name' => 'Test volume 1',
        'handle' => 'testVolume1',
        'fs' => 'localFs',
        'sortOrder' => 5,
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1000----------------------uid',
    ],

    'deleted1' => [
        'id' => '1001',
        'name' => 'Test volume 2',
        'handle' => 'testVolume2',
        'fs' => 'localFs',
        'sortOrder' => 6,
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1001----------------------uid',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
    ],

    'deleted2' => [
        'id' => '1002',
        'name' => 'Test volume 3',
        'handle' => 'testVolume3',
        'fs' => 'localFs',
        'sortOrder' => 7,
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1002----------------------uid',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
    ],
];
