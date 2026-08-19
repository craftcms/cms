<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'driver')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->string('driver')->nullable()->after('handle');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'operations')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->json('operations')->nullable()->after('upscale');
            });
        }

        $this->updateProjectConfig(fn (array $config): array => $this->canonicalConfig($config));
    }

    /** @param Closure(array<string, mixed>): array<string, mixed> $callback */
    private function updateProjectConfig(Closure $callback): void
    {
        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            foreach ($projectConfig->get(ProjectConfig::PATH_IMAGE_TRANSFORMS) ?? [] as $uid => $config) {
                $projectConfig->set("imageTransforms.{$uid}", $callback($config));
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function canonicalConfig(array $config): array
    {
        return [
            'name' => $config['name'],
            'handle' => $config['handle'],
            'driver' => $config['driver'] ?? null,
            'operations' => [
                'fill' => null,
                'format' => null,
                'height' => null,
                'interlace' => 'none',
                'mode' => 'crop',
                'position' => 'center-center',
                'quality' => null,
                'upscale' => true,
                'width' => null,
                ...Arr::except($config, ['name', 'handle', 'driver', 'operations']),
                ...(is_array($config['operations'] ?? null) ? $config['operations'] : []),
            ],
        ];
    }
};
