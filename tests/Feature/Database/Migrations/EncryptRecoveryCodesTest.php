<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

test('encrypts existing recovery codes and decrypts them on rollback', function () {
    $codes = json_encode(['abc123-def456'], JSON_THROW_ON_ERROR);
    $id = DB::table(Table::RECOVERYCODES)->insertGetId([
        'userId' => 1,
        'recoveryCodes' => $codes,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_07_15_000000_encrypt_recovery_codes.php';
    $migration->up();

    $encryptedCodes = DB::table(Table::RECOVERYCODES)->where('id', $id)->value('recoveryCodes');

    expect($encryptedCodes)->not->toBe($codes)
        ->and(Crypt::decryptString($encryptedCodes))->toBe($codes);

    $migration->down();

    expect(DB::table(Table::RECOVERYCODES)->where('id', $id)->value('recoveryCodes'))->toBe($codes);
});
