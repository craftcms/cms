<?php

namespace CraftCms\Cms\Console\Commands\Twig;

use craft\console\Application;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Give;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Twig\Cache\NullCache;
use Twig\Error\Error;

final class TwigCacheCommand extends Command
{
    use CraftCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'craft:twig:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Compile all of the application's Twig templates";

    public function handle(#[Give('Craft')] Application $craft): void
    {
        $originalCache = $craft->getView()->getTwig()->getCache();

        if ($originalCache instanceof NullCache) {
            // There's no point to warm up a cache that won't be used afterward
            $this->components->info('Twig cache is a NullCache, no templates cached.');

            return;
        }

        $this->callSilent('craft:twig:clear');

        $this->paths()->each(function ($path) use ($craft) {
            $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

            $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

            $this->compileTemplates($craft, $this->twigFilesIn([$path]));
        });

        $this->newLine();

        $this->components->info('Twig templates cached successfully.');
    }

    /**
     * Compile the given view files.
     */
    protected function compileTemplates(Application $craft, Collection $views): void
    {
        $views->map(function (SplFileInfo $file) use ($craft) {
            $this->components->task('    '.$file->getRelativePathname(), null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            try {
                $craft->getView()->setTemplateMode('site');
                $craft->getView()->getTwig()->load($file->getRelativePathname());
            } catch (Error $e) {
                $this->error($e->getMessage());
            }
        });

        if ($this->output->isVeryVerbose()) {
            $this->newLine();
        }
    }

    /**
     * Get the Twig files in the given path.
     */
    protected function twigFilesIn(array $paths): Collection
    {
        return Collection::make(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name('*.twig')
                ->files()
        );
    }

    /**
     * Get all the possible view paths.
     */
    protected function paths(): Collection
    {
        $finder = $this->laravel['view']->getFinder();

        return Collection::make($finder->getPaths())->merge(
            (Collection::make($finder->getHints()))->flatten()
        );
    }
}
