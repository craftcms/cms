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
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1000----------------------uid',
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
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1001----------------------uid',
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
        'fieldLayoutType' => 'volume_field_layout',
        'uid' => 'volume-1002----------------------uid',
        'dateDeleted' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d')

    ],
];
