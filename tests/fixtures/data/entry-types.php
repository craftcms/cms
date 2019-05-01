<?php

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
        'sectionId' => '1000',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '1',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ],
    [
        'id' => '1002',
        'sectionId' => '1000',
        'fieldLayoutId' => null,
        'name' => 'Test 1',
        'handle' => 'test1',
        'titleLabel' => 'Title',
        'titleFormat' => null,
        'sortOrder' => '1',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ]
];
