<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Twig;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Console\Command;

class TwigClearCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:twig:clear';

    #[\Override]
    protected $description = "Clear all the application's compiled Twig templates";

    public function handle(Twig $twig): void
    {
        foreach (TemplateMode::cases() as $mode) {
            $cache = $twig->get($mode)->getCache();

            if (! is_dir($cache)) {
                continue;
            }

            File::cleanDirectory($cache);
        }

        $this->components->info('Twig compilation cache cleared successfully.');
    }
}
