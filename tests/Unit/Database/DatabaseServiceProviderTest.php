<?php

declare(strict_types=1);

use CraftCms\Cms\Database\DatabaseServiceProvider;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;

it('uses the write connection for console requests', function () {
    $db = DB::connection();
    $writePdo = new PDO('sqlite::memory:');
    $readPdo = new PDO('sqlite::memory:');
    $db->setPdo($writePdo);
    $db->setReadPdo($readPdo);
    $db->useWriteConnectionWhenReading(false);

    new DatabaseServiceProvider(app())->boot(
        app(Repository::class),
        $db,
        app(CacheRepository::class),
    );

    expect($db->getReadPdo())->toBe($writePdo);
});
