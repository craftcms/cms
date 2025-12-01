<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Commands;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;

use function Laravel\Prompts\table;

final class InfoCommand extends Command
{
    use ChecksLicense;
    use Colors;
    use CraftCommand;
    use DrawsBoxes;
    use FetchesUpdates;

    protected $signature = 'craft:update:info';

    protected $description = 'Displays info about available updates.';

    protected $aliases = ['update/info'];

    public function handle(Plugins $plugins): int
    {
        if (! is_null($exitCode = $this->checkLicense())) {
            return $exitCode;
        }

        $updatesData = $this->fetchUpdates();

        if (($total = $updatesData->getTotal()) === 0) {
            $this->components->success('You’re all up to date!');

            return self::SUCCESS;
        }

        $lines = [];

        if ($updatesData->cms->hasReleases()) {
            $lines[] = $this->formatLine('craft', Cms::VERSION, $updatesData->cms);
        }

        foreach ($updatesData->plugins as $pluginHandle => $pluginUpdate) {
            if (! $pluginUpdate->hasReleases()) {
                continue;
            }

            try {
                $pluginInfo = $plugins->getPluginInfo($pluginHandle);
            } catch (InvalidPluginException) {
                continue;
            }

            if (! $pluginInfo['isInstalled']) {
                continue;
            }

            $lines[] = $this->formatLine($pluginHandle, $pluginInfo['version'], $pluginUpdate);
        }

        $this->newLine();
        $this->components->info($this->green(sprintf(
            ' You’ve got <fg=green;options=bold>%s</> available %s:',
            $total === 1 ? 'one' : $total,
            Str::plural('update', $total),
        )));

        table(
            [Str::padRight('Handle', 10), Str::padRight('From', 10),  Str::padRight('To', 10), Str::padRight('Status', 10)],
            $lines,
        );

        $this->components->info(implode(' ', [
            ' Run',
            $this->cyan('php craft update all'),
            $this->green('or'),
            $this->cyan('php craft update {handle}'),
            $this->green('to perform an update.'),
        ]));

        return self::SUCCESS;
    }
}
