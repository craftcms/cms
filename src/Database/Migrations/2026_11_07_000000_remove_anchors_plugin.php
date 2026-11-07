<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The `|anchors` filter and `@anchors` directive are part of Craft now, so the Anchors plugin's install record is obsolete.
        DB::table(Table::PLUGINS)->where('handle', 'anchors')->delete();

        $projectConfig = app(ProjectConfig::class);
        $path = ProjectConfig::PATH_PLUGINS.'.anchors';

        if ($projectConfig->get($path) !== null) {
            $projectConfig->remove($path, 'Remove the Anchors plugin, which is part of Craft now');
        }
    }
};
