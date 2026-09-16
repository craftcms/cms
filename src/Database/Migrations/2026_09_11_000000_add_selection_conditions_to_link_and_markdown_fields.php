<?php

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Conditions\ViewableConditionRule as AssetViewableConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ViewableConditionRule as EntryViewableConditionRule;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);

        $this->updateLinkFields($projectConfig);
        $this->updateMarkdownFields($projectConfig);
    }

    private function updateLinkFields(ProjectConfig $projectConfig): void
    {
        $fields = $this->findFields($projectConfig, Link::class);

        foreach ($fields as $path => $config) {
            if (! isset($config['settings']['typeSettings']) || ! is_array($config['settings']['typeSettings'])) {
                continue;
            }

            if ($this->updateLinkTypeSettings($config['settings']['typeSettings'])) {
                $config['settings'] = ProjectConfigHelper::packAssociativeArrays($config['settings']);
                $projectConfig->set($path, $config);
            }
        }
    }

    private function updateMarkdownFields(ProjectConfig $projectConfig): void
    {
        $fields = $this->findFields($projectConfig, Markdown::class);

        foreach ($fields as $path => $config) {
            if (! isset($config['settings']) || ! is_array($config['settings'])) {
                continue;
            }

            $linkTypesChanged = false;

            if (isset($config['settings']['linkSettingsTypeSettings']) && is_array($config['settings']['linkSettingsTypeSettings'])) {
                $linkTypesChanged = $this->updateLinkTypeSettings($config['settings']['linkSettingsTypeSettings']);
            }

            // Markdown fields have their own “Show unpermitted files” setting for their built-in asset embedding,
            // separate from the one that used to live on their embedded Asset link type
            $rules = [];
            $showUnpermittedFiles = Arr::pull($config['settings'], 'showUnpermittedFiles') ?? false;

            if (! $showUnpermittedFiles) {
                $rules[] = new AssetViewableConditionRule(['value' => true]);
            }

            $assetSettingsChanged = $this->addSelectionCondition($config['settings'], AssetCondition::class, $rules, 'assetSelectionCondition');

            if ($linkTypesChanged || $assetSettingsChanged) {
                $config['settings'] = ProjectConfigHelper::packAssociativeArrays($config['settings']);
                $projectConfig->set($path, $config);
            }
        }
    }

    /** @param  array<string, mixed>  $typeSettings */
    private function updateLinkTypeSettings(array &$typeSettings): bool
    {
        $entryChanged = $this->updateEntryLinkType($typeSettings);
        $assetChanged = $this->updateAssetLinkType($typeSettings);

        return $entryChanged || $assetChanged;
    }

    /** @param  array<string, mixed>  $typeSettings */
    private function updateEntryLinkType(array &$typeSettings): bool
    {
        if (! isset($typeSettings['entry']) || ! is_array($typeSettings['entry'])) {
            return false;
        }

        $rules = [];
        $showUnpermittedEntries = Arr::pull($typeSettings['entry'], 'showUnpermittedEntries') ?? false;

        if (! $showUnpermittedEntries) {
            $rules[] = new EntryViewableConditionRule(['value' => true]);
        }

        return $this->addSelectionCondition($typeSettings['entry'], EntryCondition::class, $rules);
    }

    /** @param  array<string, mixed>  $typeSettings */
    private function updateAssetLinkType(array &$typeSettings): bool
    {
        if (! isset($typeSettings['asset']) || ! is_array($typeSettings['asset'])) {
            return false;
        }

        $rules = [];
        $showUnpermittedFiles = Arr::pull($typeSettings['asset'], 'showUnpermittedFiles') ?? false;
        $allowedKinds = Arr::pull($typeSettings['asset'], 'allowedKinds');

        if (! $showUnpermittedFiles) {
            $rules[] = new AssetViewableConditionRule(['value' => true]);
        }

        if ($allowedKinds) {
            $rules[] = new FileTypeConditionRule(['values' => $allowedKinds]);
        }

        return $this->addSelectionCondition($typeSettings['asset'], AssetCondition::class, $rules);
    }

    /**
     * @template T of FieldInterface
     *
     * @param  class-string<T>  $type
     * @return array<string, array{type: class-string<T>}>
     */
    private function findFields(ProjectConfig $projectConfig, string $type): array
    {
        $fields = $projectConfig->find(fn (array $config) => isset($config['type']) && $config['type'] === $type);

        foreach ($fields as &$config) {
            if (isset($config['settings'])) {
                $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings']);
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  class-string<ElementConditionInterface>  $conditionClass
     * @param  ConditionRuleInterface[]  $rules
     */
    private function addSelectionCondition(array &$settings, string $conditionClass, array $rules, string $conditionKey = 'selectionCondition'): bool
    {
        if (empty($rules)) {
            return false;
        }

        $settings[$conditionKey] = [
            'class' => $conditionClass,
            'conditionRules' => [
                'operator' => 'and',
                'rules' => array_map(fn (ConditionRuleInterface $rule) => $rule->getConfig(), $rules),
            ],
        ];

        return true;
    }

    public function down(): void {}
};
