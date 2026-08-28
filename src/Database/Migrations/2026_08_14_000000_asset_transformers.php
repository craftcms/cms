<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn(Table::VOLUMES, 'assetTransformer')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->string('assetTransformer')->nullable()->after('subpath');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'parameters')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table): void {
                $table->json('parameters')->nullable()->after('upscale');
            });
        }

        $hasTransformFs = Schema::hasColumn(Table::VOLUMES, 'transformFs');
        $hasTransformSubpath = Schema::hasColumn(Table::VOLUMES, 'transformSubpath');

        if ($hasTransformFs || $hasTransformSubpath) {
            $this->migrateTransformDestinations($hasTransformFs, $hasTransformSubpath);
        }

        if ($hasTransformFs) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->dropColumn('transformFs');
            });
        }

        if ($hasTransformSubpath) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->dropColumn('transformSubpath');
            });
        }
    }

    private function migrateTransformDestinations(bool $hasTransformFs, bool $hasTransformSubpath): void
    {
        $columns = ['uid', 'name'];

        if ($hasTransformFs) {
            $columns[] = 'transformFs';
        }

        if ($hasTransformSubpath) {
            $columns[] = 'transformSubpath';
        }

        $destinations = [];

        foreach (DB::table(Table::VOLUMES)->select($columns)->orderBy('name')->get() as $volume) {
            $filesystem = $hasTransformFs && is_string($volume->transformFs) && $volume->transformFs !== ''
                ? $volume->transformFs
                : null;
            $subpath = $hasTransformSubpath && is_string($volume->transformSubpath)
                ? $volume->transformSubpath
                : '';

            if ($filesystem === null && $subpath === '') {
                continue;
            }

            $key = hash('sha256', serialize([$filesystem, $subpath]));
            $destinations[$key] ??= [
                'name' => $volume->name,
                'filesystem' => $filesystem,
                'subpath' => $subpath,
                'volumeUids' => [],
            ];
            $destinations[$key]['volumeUids'][] = $volume->uid;
        }

        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            foreach ($destinations as $key => $destination) {
                $handle = 'legacyTransforms'.ucfirst(substr($key, 0, 12));

                app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
                    'uid' => Uuid::uuid5(Uuid::NAMESPACE_URL, "craftcms:asset-transformer:{$key}")->toString(),
                    'name' => "{$destination['name']} Transforms",
                    'handle' => $handle,
                    'driver' => 'craft',
                    'settings' => [
                        'filesystem' => $destination['filesystem'],
                        'subpath' => $destination['subpath'],
                    ],
                ]), false);

                DB::table(Table::VOLUMES)
                    ->whereIn('uid', $destination['volumeUids'])
                    ->update(['assetTransformer' => $handle]);

                foreach ($destination['volumeUids'] as $volumeUid) {
                    $projectConfig->set(
                        ProjectConfig::PATH_VOLUMES.".{$volumeUid}.assetTransformer",
                        $handle,
                        'Migrate the volume transform destination',
                    );
                }
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        $projectConfig->saveModifiedConfigData();
    }
};
