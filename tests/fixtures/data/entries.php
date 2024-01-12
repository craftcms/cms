<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Theories of life',
        'plainTextField' => 'foo',
        'appointments' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],

    // Authors
    [
        'authorIds' => [1, 2, 3],
        'sectionId' => '1011',
        'typeId' => '1011',
        'title' => 'Theories of life multi-author',
    ],

    // Deleted
    [
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Deleted today',
        'dateDeleted' => (new DateTime('now'))->format('Y-m-d H:i:s'),
        'plainTextField' => 'foo',
        'appointments' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],
    [
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Deleted 40 days ago',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P40D'))->format('Y-m-d H:i:s'),
        'plainTextField' => 'foo',
        'appointments' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],
    [
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Deleted 25 days ago',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P25D'))->format('Y-m-d H:i:s'),
        'plainTextField' => 'foo',
        'appointments' => [['col1' => 'foo', 'col2' => '2019-06-25 07:00:00', 'col3' => '7', 'col4' => '1']],
    ],

    [
        'authorId' => '1',
        'sectionId' => '1003',
        'typeId' => '1003',
        'title' => 'With URL 1',
    ],
    [
        'authorId' => '1',
        'sectionId' => '1003',
        'typeId' => '1003',
        'title' => 'With URL 2',
    ],

    [
        'authorId' => '1',
        'sectionId' => '1003',
        'typeId' => '1003',
        'title' => 'Pending 1',
    ],
    [
        'authorId' => '1',
        'sectionId' => '1003',
        'typeId' => '1003',
        'title' => 'Pending 2',
    ],
    [
        'authorId' => '1',
        'sectionId' => '1004',
        'typeId' => '1004',
        'title' => 'With versioning',
    ],
    [
        'authorId' => '1',
        'sectionId' => '1005',
        'typeId' => '1005',
        'title' => 'Single entry',
    ],

];
