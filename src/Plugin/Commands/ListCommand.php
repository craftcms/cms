<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:plugin:list';

    #[\Override]
    protected $aliases = ['plugin', 'plugin/list'];

    #[\Override]
    protected $description = 'Installs a plugin.';

    public function handle(Plugins $plugins): void
    {
        $tableData = $plugins->getAllPluginInfo()
            ->sortBy(['isEnabled', 'isInstalled'], SORT_REGULAR, true)
            ->map(function (array $info, string $handle) {
                if ($info['isEnabled']) {
                    $name = "<fg=green>{$info['name']}</>";
                } elseif ($info['isInstalled']) {
                    $name = "<fg=yellow>{$info['name']}</>";
                } else {
                    $name = "<fg=gray>{$info['name']}</>";
                }

                return [
                    $name,
                    $handle,
                    $info['packageName'],
                    $info['version'],
                    $info['isInstalled'] ? '<fg=green>✓</>' : '<fg=red>x</>',
                    $info['isEnabled'] ? '<fg=green>✓</>' : '<fg=red>x</>',
                ];
            });

        $this->table(['Name', 'Handle', 'Package Name', 'Version', 'Installed', 'Enabled'], $tableData);
    }
}
