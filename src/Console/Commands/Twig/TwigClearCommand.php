<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Twig;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class TwigClearCommand extends Command
{
    use CraftCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'craft:twig:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear all the application's compiled Twig templates";

    public function handle(#[Give('Craft')] Application $craft): void
    {
        $cache = $craft->getView()->getTwig()->getCache();

        throw_unless(is_dir($cache), new RuntimeException('Twig cache path not found.'));

        File::cleanDirectory($cache);

        $this->components->info('Twig compilation cache cleared successfully.');
    }
}
