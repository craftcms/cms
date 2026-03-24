<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Console\Command;
use Override;

class RepairCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:project-config:repair
        {--dry-run : Report double-packed associative arrays without repairing them.}
    ';

    #[Override]
    protected $description = 'Repairs double-packed associative arrays in the project config.';

    #[Override]
    protected $aliases = ['project-config/repair', 'pc:repair', 'pc/repair', 'utils/repair/project-config', 'repair:project-config', 'repair/project-config'];

    public function handle(ProjectConfig $projectConfig): int
    {
        $useExternalConfig = $projectConfig->areChangesPending(force: true);
        $config = $projectConfig->get(getFromExternalConfig: $useExternalConfig);
        $repairCount = 0;

        $this->components->info('Repairing project config...');

        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $this->repairItem($projectConfig, (string) $key, $value, $repairCount, $useExternalConfig);
            }
        }

        if ($repairCount === 0) {
            $this->components->success('Nothing to repair.');

            return self::SUCCESS;
        }

        $prefix = $this->option('dry-run') ? '[DRY RUN] ' : '';

        $this->components->success("{$prefix}Finished repairing project config. $repairCount ".($repairCount === 1 ? 'item was' : 'items were').' matched.');

        return self::SUCCESS;
    }

    private function repairItem(
        ProjectConfig $projectConfig,
        string $path,
        mixed $value,
        int &$repairCount,
        bool $useExternalConfig,
    ): mixed {
        if (! is_array($value)) {
            return $value;
        }

        if (isset($value[ProjectConfig::ASSOC_KEY])) {
            $doublePacked = false;

            while (
                isset($value[ProjectConfig::ASSOC_KEY][0][0]) &&
                $value[ProjectConfig::ASSOC_KEY][0][0] === ProjectConfig::ASSOC_KEY
            ) {
                $value[ProjectConfig::ASSOC_KEY] = $value[ProjectConfig::ASSOC_KEY][0][1] ?? [];
                $doublePacked = true;
            }

            if ($doublePacked) {
                $repairCount++;
                $this->line("  ⇂ $path");

                if (! $this->option('dry-run')) {
                    $this->saveConfig($projectConfig, $path, $value, $useExternalConfig);
                }
            }
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->repairItem($projectConfig, "$path.$key", $nestedValue, $repairCount, $useExternalConfig);
        }

        return $value;
    }

    private function saveConfig(ProjectConfig $projectConfig, string $path, array $value, bool $useExternalConfig): void
    {
        if ($useExternalConfig) {
            $currentValue = $projectConfig->get($path);

            if (is_array($currentValue)) {
                $value = array_replace($currentValue, $value);
            }
        }

        $projectConfig->set($path, $value);
    }
}
