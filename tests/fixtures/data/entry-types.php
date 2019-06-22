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
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'uid' => 'entry-type-1000------------------uid'
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
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'uid' => 'entry-type-1001------------------uid'
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
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'uid' => 'entry-type-1002------------------uid'
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
        'uid' => 'entry-type-1003------------------uid'
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
        'uid' => 'entry-type-1004------------------uid'
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
        'uid' => 'entry-type-1005------------------uid'
    ]
];
