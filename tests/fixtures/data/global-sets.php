<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'name' => 'A global set',
        'handle' => 'aGlobalSet',
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'plainTextField' => 'There is some information here',
        'uid' => 'globalset-1000-------------------uid',
    ],

    [
        'name' => 'A different global set',
        'handle' => 'aDifferentGlobalSet',
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'plainTextField' => 'No more information to give.',
        'uid' => 'globalset-1001-------------------uid',
    ],

    [
        'name' => 'A third global set',
        'handle' => 'aThirdGlobalSet',
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        'plainTextField' => 'No more information to give.',
        'uid' => 'globalset-1002-------------------uid',
    ],

    // Deleted
    [
        'name' => 'A deleted global set',
        'handle' => 'aDeletedGlobalSet',
        'dateDeleted' => (new DateTime('now'))->format('Y-m-d H:i:s'),
        'uid' => 'globalset-1003-------------------uid',
    ],
];
