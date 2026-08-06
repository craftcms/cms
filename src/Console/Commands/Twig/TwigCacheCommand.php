<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Twig;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;
use Twig\Cache\NullCache;

class TwigCacheCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:twig:cache';

    #[\Override]
    protected $description = "Compile all of the application's Twig templates";

    public function handle(Twig $twig): void
    {
        $originalCache = $twig->get()->getCache();

        if ($originalCache instanceof NullCache) {
            // There's no point to warm up a cache that won't be used afterward
            $this->components->info('Twig cache is a NullCache, no templates cached.');

            return;
        }

        $this->callSilent('craft:twig:clear');

        foreach (TemplateMode::cases() as $mode) {
            TemplateMode::set($mode);

            $templateRoots = $mode->templateRoots();

            collect($templateRoots)
                ->put('', [$mode->templatesPath()])
                ->each(function ($paths, $root) use ($mode, $twig) {
                    $prefix = $this->output->isVeryVerbose() ? '<fg=yellow;options=bold>DIR</> ' : '';

                    foreach ($paths as $path) {
                        $files = $this->twigFilesIn([$path]);

                        if (! $files->count()) {
                            continue;
                        }

                        $this->components->task($prefix.$path, null, OutputInterface::VERBOSITY_VERBOSE);

                        $this->compileTemplates($twig, $mode, $root, $files);
                    }
                });
        }

        $this->newLine();

        $this->components->info('Twig templates cached successfully.');
    }

    /**
     * Compile the given view files.
     *
     * @param  Collection<string, SplFileInfo>  $views
     */
    private function compileTemplates(Twig $twig, TemplateMode $mode, string $root, Collection $views): void
    {
        $views->map(function (SplFileInfo $file) use ($mode, $twig, $root) {
            $relativePathName = $root ? $root.'/'.$file->getRelativePathname() : $file->getRelativePathname();

            try {
                $twig->get($mode)->load($relativePathName);

                $this->components->task("    $relativePathName", null, OutputInterface::VERBOSITY_VERY_VERBOSE);
            } catch (Throwable $e) {
                $this->error('    '.$e->getMessage());
            }
        });

        if ($this->output->isVeryVerbose()) {
            $this->newLine();
        }
    }

    /**
     * @param  list<string>  $paths
     * @return Collection<string, SplFileInfo>
     */
    private function twigFilesIn(array $paths): Collection
    {
        return Collection::make(
            Finder::create()
                ->in($paths)
                ->exclude('vendor')
                ->name(['*.twig', '*.html'])
                ->files()
        );
    }
}
