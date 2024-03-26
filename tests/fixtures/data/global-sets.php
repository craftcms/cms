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
        'fieldLayoutUid' => 'field-layout-1004----------------uid',
        'plainTextField3' => 'There is some information here',
        'uid' => 'globalset-1000-------------------uid',
        'appointments2' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],

    [
        'name' => 'A different global set',
        'handle' => 'aDifferentGlobalSet',
        'fieldLayoutUid' => 'field-layout-1004----------------uid',
        'plainTextField3' => 'No more information to give.',
        'uid' => 'globalset-1001-------------------uid',
        'appointments2' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],

    [
        'name' => 'A third global set',
        'handle' => 'aThirdGlobalSet',
        'fieldLayoutUid' => 'field-layout-1004----------------uid',
        'plainTextField3' => 'No more information to give.',
        'uid' => 'globalset-1002-------------------uid',
        'appointments2' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],

    // Deleted
    [
        'name' => 'A deleted global set',
        'handle' => 'aDeletedGlobalSet',
        'dateDeleted' => (new DateTime('now'))->format('Y-m-d H:i:s'),
        'uid' => 'globalset-1003-------------------uid',
    ],
];
