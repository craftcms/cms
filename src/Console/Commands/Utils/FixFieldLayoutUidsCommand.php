<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Override;

/**
 * @phpstan-type ConfigArray array<array-key, mixed>
 * @phpstan-type Uids array<string, true>
 */
class FixFieldLayoutUidsCommand extends Command
{
    use CraftCommand;

    /** @var array<string, list<string>> */
    private array $topLevelUids = [];

    #[Override]
    protected $signature = 'craft:utils:fix-field-layout-uids';

    #[Override]
    protected $description = 'Fixes duplicate or missing field layout component UIDs in project config.';

    #[Override]
    protected $aliases = ['utils/fix-field-layout-uids', 'utils/fix-field-layout-uids:index', 'utils/fix-field-layout-uids/index'];

    public function handle(ProjectConfig $projectConfig): int
    {
        $this->topLevelUids = [];
        $useExternalConfig = $projectConfig->areChangesPending(force: true);

        $this->components->info('Looking for duplicate UUIDs ...');

        $count = 0;
        $uids = [];
        $config = $projectConfig->get(getFromExternalConfig: $useExternalConfig);

        if (is_array($config)) {
            $this->fixUids($config, $count, $projectConfig, $useExternalConfig, uids: $uids);
            $this->fixFieldLayoutUids($count, $projectConfig, $useExternalConfig);
        }

        $summary = $count
            ? sprintf('Fixed %s duplicate or missing %s.', $count, $count > 1 ? 'UUIDs' : 'UUID')
            : 'No duplicate UUIDs were found.';

        $this->components->success("Done. $summary");

        return self::SUCCESS;
    }

    /**
     * @param  ConfigArray  $config
     * @param  Uids  $uids
     */
    private function fixUids(
        array $config,
        int &$count,
        ProjectConfig $projectConfig,
        bool $useExternalConfig,
        string $path = '',
        array &$uids = [],
    ): void {
        if (is_array($config['fieldLayouts'] ?? null)) {
            $this->fixFieldLayouts($config, $count, $projectConfig, $useExternalConfig, $path, $uids);

            return;
        }

        if (is_array($config['fieldLayout'] ?? null)) {
            $this->fixFieldLayout($config, $count, $projectConfig, $useExternalConfig, $path, $uids);

            return;
        }

        foreach ($config as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $this->fixUids($value, $count, $projectConfig, $useExternalConfig, $this->appendPath($path, (string) $key), $uids);
        }
    }

    /**
     * @param  ConfigArray  $config
     * @param  Uids  $uids
     */
    private function fixFieldLayouts(
        array $config,
        int &$count,
        ProjectConfig $projectConfig,
        bool $useExternalConfig,
        string $path,
        array &$uids,
    ): void {
        $modified = false;
        $packed = isset($config['fieldLayouts'][ProjectConfig::ASSOC_KEY]);
        $fieldLayouts = $packed
            ? ProjectConfigHelper::unpackAssociativeArray($config['fieldLayouts'])
            : $config['fieldLayouts'];

        foreach ($fieldLayouts as $fieldLayoutUid => &$fieldLayoutConfig) {
            $this->topLevelUids[$fieldLayoutUid][] = $path;

            if (! is_array($fieldLayoutConfig)) {
                continue;
            }

            $this->fixUidsInLayout(
                $fieldLayoutConfig,
                $count,
                $this->appendPath($path, "fieldLayouts.$fieldLayoutUid"),
                $uids,
                $modified,
            );
        }

        unset($fieldLayoutConfig);

        if (! $modified) {
            return;
        }

        $config['fieldLayouts'] = $packed
            ? ProjectConfigHelper::packAssociativeArray($fieldLayouts)
            : $fieldLayouts;

        $this->saveConfig($projectConfig, $path, $config, 'fieldLayouts', $useExternalConfig);
    }

    /**
     * @param  ConfigArray  $config
     * @param  Uids  $uids
     */
    private function fixFieldLayout(
        array $config,
        int &$count,
        ProjectConfig $projectConfig,
        bool $useExternalConfig,
        string $path,
        array &$uids,
    ): void {
        $modified = false;
        $packed = isset($config['fieldLayout'][ProjectConfig::ASSOC_KEY]);
        $fieldLayout = $packed
            ? ProjectConfigHelper::unpackAssociativeArray($config['fieldLayout'])
            : $config['fieldLayout'];

        $this->fixUidsInLayout(
            $fieldLayout,
            $count,
            $this->appendPath($path, 'fieldLayout'),
            $uids,
            $modified,
        );

        if (! $modified) {
            return;
        }

        $config['fieldLayout'] = $packed
            ? ProjectConfigHelper::packAssociativeArray($fieldLayout)
            : $fieldLayout;

        $this->saveConfig($projectConfig, $path, $config, 'fieldLayout', $useExternalConfig);
    }

    /**
     * @param  ConfigArray  $fieldLayoutConfig
     * @param  Uids  $uids
     */
    private function fixUidsInLayout(
        array &$fieldLayoutConfig,
        int &$count,
        string $path,
        array &$uids,
        bool &$modified,
    ): void {
        if (! isset($fieldLayoutConfig['tabs']) || ! is_array($fieldLayoutConfig['tabs'])) {
            return;
        }

        foreach ($fieldLayoutConfig['tabs'] as $tabIndex => &$tabConfig) {
            if (! is_array($tabConfig)) {
                continue;
            }

            $tabPath = "$path.tabs.$tabIndex";
            $this->checkUid($tabConfig, $count, $uids, $modified, $tabPath);
            if (! isset($tabConfig['elements'])) {
                continue;
            }
            if (! is_array($tabConfig['elements'])) {
                continue;
            }

            foreach ($tabConfig['elements'] as $elementIndex => &$elementConfig) {
                if (! is_array($elementConfig)) {
                    continue;
                }

                $this->checkUid($elementConfig, $count, $uids, $modified, "$tabPath.elements.$elementIndex");
            }

            unset($elementConfig);
        }

        unset($tabConfig);
    }

    /**
     * @param  ConfigArray  $config
     * @param  Uids  $uids
     */
    private function checkUid(array &$config, int &$count, array &$uids, bool &$modified, string $path): void
    {
        $uid = $config['uid'] ?? null;

        if (! is_string($uid) || $uid === '') {
            $reason = 'Missing UUID found at';
        } elseif (isset($uids[$uid])) {
            $reason = 'Duplicate UUID at';
        } else {
            $uids[$uid] = true;

            return;
        }

        $newUid = (string) Str::uuid();

        $this->components->task(
            "$reason $path. Setting to $newUid",
            function () use (&$config, &$count, &$modified, $newUid) {
                $config['uid'] = $newUid;
                $count++;
                $modified = true;
            }
        );

        $uids[$newUid] = true;
    }

    private function fixFieldLayoutUids(int &$count, ProjectConfig $projectConfig, bool $useExternalConfig): void
    {
        foreach ($this->topLevelUids as $fieldLayoutUid => $paths) {
            if (count($paths) === 1) {
                unset($this->topLevelUids[$fieldLayoutUid]);
            }
        }

        foreach ($this->topLevelUids as $fieldLayoutUid => $paths) {
            array_shift($paths);

            foreach ($paths as $path) {
                $config = $projectConfig->get($path, $useExternalConfig);
                if (! is_array($config)) {
                    continue;
                }
                if (! is_array($config['fieldLayouts'] ?? null)) {
                    continue;
                }

                $packed = isset($config['fieldLayouts'][ProjectConfig::ASSOC_KEY]);
                $fieldLayouts = $packed
                    ? ProjectConfigHelper::unpackAssociativeArray($config['fieldLayouts'])
                    : $config['fieldLayouts'];

                if (! array_key_exists($fieldLayoutUid, $fieldLayouts)) {
                    continue;
                }

                $newUid = (string) Str::uuid();
                $fieldLayoutConfig = $fieldLayouts[$fieldLayoutUid];
                unset($fieldLayouts[$fieldLayoutUid]);
                $fieldLayouts[$newUid] = $fieldLayoutConfig;
                $config['fieldLayouts'] = $packed
                    ? ProjectConfigHelper::packAssociativeArray($fieldLayouts)
                    : $fieldLayouts;

                $this->components->task(
                    sprintf('Duplicate UUID at %s. Setting to %s', $this->appendPath($path, "fieldLayouts.$fieldLayoutUid"), $newUid),
                    function () use (&$count, $config, $path, $projectConfig, $useExternalConfig) {
                        $this->saveConfig($projectConfig, $path, $config, 'fieldLayouts', $useExternalConfig);
                        $count++;
                    }
                );
            }
        }
    }

    private function appendPath(string $path, string $suffix): string
    {
        return $path !== '' ? "$path.$suffix" : $suffix;
    }

    /** @param ConfigArray $config */
    private function saveConfig(ProjectConfig $projectConfig, string $path, array $config, string $rootKey, bool $useExternalConfig): void
    {
        if ($path === '') {
            $projectConfig->set($rootKey, $config[$rootKey]);

            return;
        }

        if ($useExternalConfig) {
            $currentConfig = $projectConfig->get($path);

            if (is_array($currentConfig)) {
                $config = array_replace($currentConfig, $config);
            }
        }

        $projectConfig->set($path, $config);
    }
}
