<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\fs\Local;
use crafttests\fixtures\FsFixture;

return [
    'localFs' => [
        'id' => '1000',
        'name' => 'Local FS',
        'type' => Local::class,
        'url' => null,
        'hasUrls' => true,
        'settings' => [
            'path' => dirname(__FILE__, 3) . '/_data/assets/volume-folder-1/',
            'url' => FsFixture::BASE_URL,
        ],
    ],
];
