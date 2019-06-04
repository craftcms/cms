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
    ],

    [
        'name' => 'A different global set',
        'handle' => 'aDifferentGlobalSet',
    ],

    // Deleted
    [
        'name' => 'A deleted global set',
        'handle' => 'aDeletedGlobalSet',
        'dateDeleted' => (new DateTime('now'))->format('Y-m-d H:i:s'),
    ],
];
