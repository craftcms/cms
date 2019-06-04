<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\volumes\Local;
use crafttests\fixtures\VolumesFixture;

return [
    'basic-volume' => [
        'id' => '1000',
        'name' => 'Test volume 1',
        'handle' => 'testVolume1',
        'type' => Local::class,
        'url' => null,
        'hasUrls' => true,
        'settings' => json_encode([
            'path' => dirname(__FILE__, 3) . '/_data/assets/volume-folder-1/',
            'url' => VolumesFixture::BASE_URL
        ]),
        'sortOrder' => 5,
        'fieldLayoutId' => null,
        'uid' => '09a48e85-2f12-44a8-b82c-45b14b13d8ce'
    ],

    'deleted1' => [
        'id' => '1001',
        'name' => 'Test volume 2',
        'handle' => 'testVolume2',
        'type' => Local::class,
        'url' => null,
        'settings' => json_encode([
            'path' => dirname(__FILE__, 3) . '/_data/assets/volume-folder-1/',
            'url' => VolumesFixture::BASE_URL
        ]),
        'hasUrls' => true,
        'sortOrder' => 6,
        'fieldLayoutId' => null,
        'uid' => '09a48e85-2f12-44a8-b82c-45b14b13d8ce',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')

    ],

    'deleted2' => [
        'id' => '1002',
        'name' => 'Test volume 3',
        'handle' => 'testVolume3',
        'type' => Local::class,
        'url' => null,
        'settings' => json_encode([
            'path' => dirname(__FILE__, 3) . '/_data/assets/volume-folder-1/',
            'url' => VolumesFixture::BASE_URL
        ]),
        'hasUrls' => true,
        'sortOrder' => 7,
        'fieldLayoutId' => null,
        'uid' => '09a48e85-2f12-44a8-b82c-45b14b13d8ce',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')

    ],
];
