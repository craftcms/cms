<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asset transformers are now chosen per disk in config/filesystems.php, so this drops the transformer profiles that
 * earlier Craft 6 alphas stored in project config, along with everything keyed by their UIDs. It also clears the
 * transform index, whose rows from Craft 5 and earlier alphas may point at transform locations that no longer apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            $projectConfig->remove('assetTransformers', 'Remove asset transformer profiles');

            foreach (array_keys($this->arrayConfig($projectConfig, ProjectConfig::PATH_VOLUMES)) as $volumeUid) {
                if ($projectConfig->get(ProjectConfig::PATH_VOLUMES.".$volumeUid.assetTransformer") !== null) {
                    $projectConfig->remove(ProjectConfig::PATH_VOLUMES.".$volumeUid.assetTransformer", 'Remove the volume asset transformer');
                }
            }

            // Named transform parameters were keyed by transformer profile UIDs.
            foreach (array_keys($this->arrayConfig($projectConfig, ProjectConfig::PATH_IMAGE_TRANSFORMS)) as $transformUid) {
                if ($projectConfig->get(ProjectConfig::PATH_IMAGE_TRANSFORMS.".$transformUid.parameters") !== null) {
                    $projectConfig->remove(ProjectConfig::PATH_IMAGE_TRANSFORMS.".$transformUid.parameters", 'Remove image transform parameters');
                }
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        $projectConfig->saveModifiedConfigData();

        if (Schema::hasColumn(Table::IMAGETRANSFORMS, 'parameters')) {
            DB::table(Table::IMAGETRANSFORMS)->update(['parameters' => null]);
        }

        if (Schema::hasColumn(Table::VOLUMES, 'assetTransformer')) {
            Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->dropColumn('assetTransformer'));
        }

        if (Schema::hasColumn(Table::IMAGETRANSFORMINDEX, 'transformer')) {
            // Rows keyed by a profile UID or a Craft 5 transformer class may point at a transform location that no longer
            // applies, so they're all dropped, and transforms are generated again as they're requested.
            DB::table(Table::IMAGETRANSFORMINDEX)->delete();

            Schema::table(Table::IMAGETRANSFORMINDEX, fn (Blueprint $table) => $table->dropColumn('transformer'));
        }
    }

    /** @return array<string, mixed> */
    private function arrayConfig(ProjectConfig $projectConfig, string $path): array
    {
        $config = $projectConfig->get($path);

        return is_array($config) ? $config : [];
    }
};
