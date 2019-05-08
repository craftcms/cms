<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

copy(dirname(__FILE__,3).'/_craft/assets/product.jpg', 'product.jpg');

return [
    [
        'tempFilePath' => 'product.jpg',
        'filename' => 'product.jpg',
        'volumeId' => '1000',
        'folderId' => '1000',
    ],
];