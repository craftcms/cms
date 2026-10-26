<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing volumes on multi-site installs should keep saving assets to all sites.
        // Single-site installs can use custom propagation, as that won't change anything until a site is added.
        $isMultiSite = DB::table(Table::SITES)->whereNull('dateDeleted')->count() > 1;
        $propagationMethod = $isMultiSite ? PropagationMethod::All : PropagationMethod::Custom;

        if (! Schema::hasColumn(Table::VOLUMES, 'propagationMethod')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table): void {
                $table->string('propagationMethod')->default(PropagationMethod::Custom->value)->after('altTranslationKeyFormat');
            });

            DB::table(Table::VOLUMES)->update(['propagationMethod' => $propagationMethod->value]);
        }

        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            foreach ($projectConfig->get(ProjectConfig::PATH_VOLUMES) ?? [] as $uid => $config) {
                if (! isset($config['propagationMethod'])) {
                    $projectConfig->set(
                        ProjectConfig::PATH_VOLUMES.".$uid.propagationMethod",
                        $propagationMethod->value,
                        'Set the volume propagation method',
                    );
                }
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }

        $projectConfig->saveModifiedConfigData();
    }

    public function down(): void {}
};
