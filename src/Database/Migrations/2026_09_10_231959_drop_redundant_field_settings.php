<?php

use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Conditions\ViewableConditionRule as AssetsViewableConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Entry\Conditions\ViewableConditionRule as EntriesAssetsViewableConditionRule;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);

        $this->updateAssetsFields($projectConfig);
        $this->updateEntriesFields($projectConfig);
    }

    private function updateAssetsFields(ProjectConfig $projectConfig): void
    {
        $assetsFields = $this->findFields($projectConfig, Assets::class);

        foreach ($assetsFields as $path => $config) {
            if (! isset($config['settings'])) {
                continue;
            }

            $rules = [];

            $restrictFiles = Arr::pull($config['settings'], 'restrictFiles');
            $allowedKinds = Arr::pull($config['settings'], 'allowedKinds');
            $showUnpermittedFiles = Arr::pull($config['settings'], 'showUnpermittedFiles');

            if ($restrictFiles && $allowedKinds) {
                $rules[] = new FileTypeConditionRule(['values' => $allowedKinds]);
            }

            if (! is_null($showUnpermittedFiles) && ! $showUnpermittedFiles) {
                $rules[] = new AssetsViewableConditionRule(['value' => true]);
            }

            $this->addRulesToFieldConfig($config, $rules);
            $projectConfig->set($path, $config);
        }
    }

    private function updateEntriesFields(ProjectConfig $projectConfig): void
    {
        $entriesFields = $this->findFields($projectConfig, Entries::class);

        foreach ($entriesFields as $path => $config) {
            if (! isset($config['settings'])) {
                continue;
            }

            $rules = [];

            $showUnpermittedEntries = Arr::pull($config['settings'], 'showUnpermittedEntries');

            if (! is_null($showUnpermittedEntries) && ! $showUnpermittedEntries) {
                $rules[] = new EntriesAssetsViewableConditionRule(['value' => true]);
            }

            $this->addRulesToFieldConfig($config, $rules);
            $projectConfig->set($path, $config);
        }
    }

    /**
     * @template T of FieldInterface
     *
     * @param  class-string<T>  $type
     * @return array<string, array{type: class-string<T>}>
     */
    private function findFields(ProjectConfig $projectConfig, string $type): array
    {
        return $projectConfig->find(fn (array $config) => isset($config['type']) && $config['type'] === $type);
    }

    /**
     * @param  array{type: class-string<FieldInterface>}  $config
     * @param  ConditionRuleInterface[]  $rules
     */
    private function addRulesToFieldConfig(array &$config, array $rules): void
    {
        if (empty($rules)) {
            return;
        }

        // Ensure the selection condition is set to a top-level AND group
        if (! isset($config['settings']['selectionCondition']['conditionRules']['rules'])) {
            $config['settings']['selectionCondition']['conditionRules'] = [
                'operator' => 'and',
                'rules' => $config['settings']['selectionCondition']['conditionRules'] ?? [],
            ];
        } elseif (($config['settings']['selectionCondition']['conditionRules']['operator'] ?? 'and') === 'or') {
            $config['settings']['selectionCondition']['conditionRules'] = [
                'operator' => 'and',
                'rules' => [
                    $config['settings']['selectionCondition']['conditionRules'],
                ],
            ];
        }

        foreach ($rules as $rule) {
            $config['settings']['selectionCondition']['conditionRules']['rules'][] = $rule->getConfig();
        }
    }

    public function down(): void {}
};
