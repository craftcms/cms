<?php

return [
    [
        'id' => '1000',
        'name' => 'Test 1',
        'handle' => 'test1',
        'type' => 'channel',
        'enableVersioning' => false,
        'propagateEntries' => true,
    ],
    [
        'id' => '1001',
        'name' => 'Test 2',
        'handle' => 'test2',
        'type' => 'channel',
        'enableVersioning' => false,
        'propagateEntries' => true,
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ],
    [
        'id' => '1002',
        'name' => 'Test 3',
        'handle' => 'test3',
        'type' => 'channel',
        'enableVersioning' => false,
        'propagateEntries' => true,
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')
    ],
];
