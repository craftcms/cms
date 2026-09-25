<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'id' => '3000',
        'name' => 'NEM Test Section',
        'handle' => 'nemTestSection',
        'type' => 'channel',
        'enableVersioning' => true,
        'propagationMethod' => 'all',
        'uid' => 'nem-section----------------------uid',
        'entryTypes' => ['3000'],
    ],
    // Lets entries choose their own sites, so a draft can add a site its canonical entry isn't in.
    [
        'id' => '3001',
        'name' => 'NEM Custom Propagation Section',
        'handle' => 'nemCustomPropagationSection',
        'type' => 'channel',
        'enableVersioning' => true,
        'propagationMethod' => 'custom',
        'uid' => 'nem-section-custom---------------uid',
        'entryTypes' => ['3002'],
    ],
];
