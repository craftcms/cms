<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

return [
    [
        'id' => '1000',
        'userId' => '1',
        'token' => 'PRRG3Xdr-qfU7Mk75T81WzKnZV5NQp50pVnQClnCbmE5fSPOLqXKYqYdyJnrtalLOGlLb2TmDyNEmE-j_oZiAn8UGdHBYcbcOAsL',
        'uid' => '307a18cf-4af8-4a95-95ca-be5cbd4ab753'
    ],
    [
        'id' => '1001',
        'userId' => '1',
        'token' => 'PRRG3Xdr-adsadsd21312312dsaaasd-j_oZiAn8UGdHBYcbcOAsL',
        'uid' => '307a18cf-153f-4a95-95ca-be5cbd4ab753'
    ],
    [
        'id' => '1002',
        'userId' => '1',
        'token' => 'PRRG3Xdr-vbbvfgdfgh5656423234234-j_oZiAn8UGdHBYcbcOAsL',
        'dateCreated' => (new DateTime('now'))->format('Y-m-d'),
        'dateUpdated' => (new DateTime('now'))->format('Y-m-d'),
        'uid' => '307a18cf-4af8-65gt-95ca-be5cbd4ab753'
    ],

    // Stale sessions
    [
        'id' => '1003',
        'userId' => '1',
        'token' => '123as4gfb5-vbbvfgdfgh5656423234234-j_oZiAn8UGdHBYcbcOAsL',
        'dateCreated' => (new DateTime('now'))->sub(new DateInterval('P4M'))->format('Y-m-d'),
        'dateUpdated' => (new DateTime('now'))->sub(new DateInterval('P4M'))->format('Y-m-d'),
        'uid' => '307a18cf-4af8-53g6-95ca-be5cbd4ab753'
    ],
    [
        'id' => '1004',
        'userId' => '1',
        'token' => '123as4gfb5-vbbvfgdfgh5656423234234-j_oZiAn8UGdHBYcbcOAsL',
        'dateCreated' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'dateUpdated' => (new DateTime('now'))->sub(new DateInterval('P3M5D'))->format('Y-m-d'),
        'uid' => '307a18cf-4af8-53g6-95ca-be5cbd4ab753'
    ],

    // Not stale
    [
        'id' => '1005',
        'userId' => '1',
        'token' => '123as4gfb5-vbbvfgdfgh5656423234234-k_oZiAn8UGdHBYcbcOAsL',
        'dateCreated' => (new DateTime('now'))->sub(new DateInterval('P2M20D'))->format('Y-m-d'),
        'dateUpdated' => (new DateTime('now'))->sub(new DateInterval('P2M20D'))->format('Y-m-d'),
        'uid' => '307a18cf-4728-53g6-95ca-be5cbd4ab753'
    ],
];
