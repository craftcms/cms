<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('drops upload sessions started on Craft 5 when adopting its table', function () {
    $session = fn (string $uploader) => UploadSession::create([
        'id' => (string) Str::uuid(),
        'owner' => 'user:1',
        'handler' => 'craft\uploads\AssetUploadHandler',
        'uploader' => $uploader,
        'disk' => 'temp',
        'filename' => 'photo.jpg',
        'size' => 3,
        'parameters' => [],
        'state' => [],
        'expiresAt' => now()->addDay(),
    ]);

    $session('direct');
    $session('craft');
    $tus = $session('tus');

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_08_101230_create_upload_sessions_table.php';
    $migration->up();

    expect(DB::table(Table::UPLOADSESSIONS)->pluck('id')->all())->toBe([$tus->id]);
});
