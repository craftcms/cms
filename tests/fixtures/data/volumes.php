<?php

return [
    'basic-volume' => [
        'id' => '1000',
        'name' => 'Test volume 1',
        'handle' => 'testVolume1',
        'type' => \craft\volumes\Local::class,
        'url' => null,
        'hasUrls' => true,
        'settings' => json_encode([
            'path' => dirname(__FILE__, 3).'/_data/assets/volume-folder-1/',
            'url' => \craftunit\fixtures\VolumesFixture::BASE_URL
        ]),
        'sortOrder' => 5,
        'fieldLayoutId' => null,
        'dateCreated' => '2018-08-08 20:00:00',
        'dateUpdated' => '2018-08-08 20:00:00',
        'uid' => '09a48e85-2f12-44a8-b82c-45b14b13d8ce'
    ],
];