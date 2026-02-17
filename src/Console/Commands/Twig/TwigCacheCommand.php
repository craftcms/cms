<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Twig;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\View\Factory as ViewFactory;
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

    public function handle(): void
    {
        $originalCache = Craft::$app->getView()->getTwig()->getCache();

        if ($originalCache instanceof NullCache) {
            // There's no point to warm up a cache that won't be used afterward
            $this->components->info('Twig cache is a NullCache, no templates cached.');

            return;
        }

        $this->callSilent('craft:twig:clear');

        $this->paths()->each(function ($path) {
            $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

            $files = $this->twigFilesIn([$path]);

            if (! $files->count()) {
                return;
            }

            $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

            $this->compileTemplates($files);
        });

        $this->newLine();

        $this->components->info('Twig templates cached successfully.');
    }

    /**
     * Compile the given view files.
     */
    private function compileTemplates(Collection $views): void
    {
        $views->map(function (SplFileInfo $file) {
            $this->components->task('    '.$file->getRelativePathname(), null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            try {
                TemplateMode::set(TemplateMode::Site);
                Craft::$app->getView()->getTwig()->load($file->getRelativePathname());
            } catch (TemplateLoaderException) {
                try {
                    TemplateMode::set(TemplateMode::Cp);
                    Craft::$app->getView()->getTwig()->load($file->getRelativePathname());
                } catch (Error $e) {
                    $this->error($e->getMessage());
                }
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
    private function twigFilesIn(array $paths): Collection
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
    private function paths(): Collection
    {
        /** @var \Illuminate\View\FileViewFinder $finder */
        $finder = $this->laravel->make(ViewFactory::class)->getFinder();

        return Collection::make($finder->getPaths())->merge(
            (Collection::make($finder->getHints()))->flatten()
        );
    }
}
