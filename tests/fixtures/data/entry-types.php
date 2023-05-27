<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'id' => '1000',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleFormat' => null,
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'uid' => 'entry-type-1000------------------uid',
    ],
    [
        'id' => '1001',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleFormat' => null,
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'uid' => 'entry-type-1001------------------uid',
    ],
    [
        'id' => '1002',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleFormat' => null,
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'uid' => 'entry-type-1002------------------uid',
    ],
    [
        'id' => '1003',
        'fieldLayoutId' => null,
        'name' => 'With URLS',
        'handle' => 'withUrls',
        'titleFormat' => null,
        'uid' => 'entry-type-1003------------------uid',
    ],
    [
        'id' => '1004',
        'fieldLayoutId' => null,
        'name' => 'With versioning',
        'handle' => 'withVersioning',
        'titleFormat' => null,
        'uid' => 'entry-type-1004------------------uid',
    ],
    [
        'id' => '1005',
        'fieldLayoutId' => null,
        'name' => 'Single',
        'handle' => 'single',
        'titleFormat' => null,
        'uid' => 'entry-type-1005------------------uid',
    ],
    [
        'id' => '1006',
        'fieldLayoutId' => null,
        'name' => 'With matrix with relations',
        'handle' => 'withMatrixWithRelations',
        'titleFormat' => null,
        'fieldLayoutType' => 'field_layout_with_matrix_with_relational_field',
        'uid' => 'entry-type-1006------------------uid',
    ],
];
