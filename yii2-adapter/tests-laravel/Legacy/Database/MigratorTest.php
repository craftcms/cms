<?php

declare(strict_types=1);

use CraftCms\Cms\Database\MigrationRepository;
use CraftCms\Cms\Database\Migrator as CoreMigrator;
use CraftCms\Yii2Adapter\Database\MigrationWrapper;
use CraftCms\Yii2Adapter\Database\Migrator;
use Illuminate\Database\Migrations\Migrator as IlluminateMigrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

afterEach(function() {
    File::delete(storage_path('m260226_120000_product_type_permissions.php'));
});

it('resolves a loaded Yii-style migration without requiring it again', function() {
    $path = storage_path('m260226_120000_product_type_permissions.php');
    File::put($path, <<<'PHP'
        <?php

        namespace CraftCms\Yii2Adapter\Tests\Fixtures;

        class m260226_120000_product_type_permissions {}
        PHP);

    $filesystem = Mockery::mock(Filesystem::class)->makePartial();
    $filesystem->shouldReceive('getRequire')->andReturn(new stdClass());

    $migrator = app(CoreMigrator::class);
    new ReflectionProperty(IlluminateMigrator::class, 'files')->setValue($migrator, $filesystem);

    $migrator->requireFiles([$path]);

    $resolvePath = new ReflectionMethod(Migrator::class, 'resolvePath');
    $migration = $resolvePath->invoke($migrator, $path);

    $filesystem->shouldNotHaveReceived('getRequire');

    expect($migrator)->toBeInstanceOf(Migrator::class);
    expect($migrator->getRepository())->toBeInstanceOf(MigrationRepository::class);
    expect($migration)->toBeInstanceOf(MigrationWrapper::class);
});
