<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'id' => '1000',
        'sectionId' => '1000',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '1',
    ],
    [
        'id' => '1001',
        'sectionId' => '1001',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '2',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ],
    [
        'id' => '1002',
        'sectionId' => '1002',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '3',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ],
    [
        'id' => '1003',
        'sectionId' => '1003',
        'fieldLayoutId' => null,
        'name' => 'With URLS',
        'handle' => 'withUrls',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '4',
    ],
    [
        'id' => '1004',
        'sectionId' => '1004',
        'fieldLayoutId' => null,
        'name' => 'With versioning',
        'handle' => 'withVersioning',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '1',
    ],
    [
        'id' => '1005',
        'sectionId' => '1005',
        'fieldLayoutId' => null,
        'name' => 'Single',
        'handle' => 'single',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '1',
    ]
];
