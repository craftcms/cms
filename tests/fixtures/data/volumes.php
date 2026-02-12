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
        'fieldLayoutUid' => 'field-layout-1001----------------uid',
        'uid' => 'volume-1000----------------------uid',
    ],

    'deleted1' => [
        'id' => '1001',
        'name' => 'Test volume 2',
        'handle' => 'testVolume2',
        'fs' => 'localFs',
        'sortOrder' => 6,
        'fieldLayoutUid' => 'field-layout-1001----------------uid',
        'uid' => 'volume-1001----------------------uid',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
    ],

    'deleted2' => [
        'id' => '1002',
        'name' => 'Test volume 3',
        'handle' => 'testVolume3',
        'fs' => 'localFs',
        'sortOrder' => 7,
        'fieldLayoutUid' => 'field-layout-1001----------------uid',
        'uid' => 'volume-1002----------------------uid',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
    ],

    'subpath' => [
        'id' => '1003',
        'name' => 'Test volume 4',
        'handle' => 'testVolume4',
        'subpath' => 'test-subpath',
        'fs' => 'localFs',
        'sortOrder' => 8,
        'fieldLayoutUid' => 'field-layout-1003----------------uid',
        'uid' => 'volume-1003----------------------uid',
    ],

];
