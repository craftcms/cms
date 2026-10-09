<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug;

use CraftCms\Cms\Debug\Deprecation\DeprecationCollectorProvider;
use CraftCms\Cms\Debug\Element\ElementCollectorProvider;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataFormatter\DataFormatter;
use Fruitcake\LaravelDebugbar\CollectorProviders\AbstractCollectorProvider;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (
            ! $this->app->bound('debugbar') ||
            ! $this->app->bound(LaravelDebugbar::class) ||
            ! class_exists(AbstractCollectorProvider::class)
        ) {
            return;
        }

        $this->app->afterResolving('debugbar', fn () => $this->registerCollectorProviders());

        if ($this->app->resolved('debugbar')) {
            $this->registerCollectorProviders();
        }
    }

    private function registerCollectorProviders(): void
    {
        $this->app->call(DeprecationCollectorProvider::class);
        $this->app->call(ElementCollectorProvider::class);

        // The debug bar replaces its data formatter when it boots, which comes
        // after it's resolved.
        $this->app->booted(fn () => $this->registerElementCaster());
    }

    /**
     * Dumps an element as its identity rather than its whole object graph, the
     * way the debug bar already summarizes Eloquent models. Panels that record
     * their arguments, like gate checks, would otherwise dump every element in
     * full, which can exhaust memory on element-heavy pages.
     */
    private function registerElementCaster(): void
    {
        $formatter = DataCollector::getDefaultDataFormatter();

        if (! $formatter instanceof DataFormatter) {
            return;
        }

        $options = $formatter->getClonerOptions();
        // `casters` replaces the default casters when it's set, so ours joins
        // whichever list is in use.
        $key = isset($options['casters']) ? 'casters' : 'additional_casters';

        $formatter->mergeClonerOptions([
            $key => [
                ...($options[$key] ?? []),
                ElementInterface::class => static fn (ElementInterface $element): array => [
                    'id' => $element->id,
                    'siteId' => $element->siteId,
                    'title' => $element->title,
                ],
            ],
        ]);
    }
}
