<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug\Deprecation;

use CraftCms\Cms\Deprecator\Deprecator;
use Fruitcake\LaravelDebugbar\CollectorProviders\AbstractCollectorProvider;

/**
 * @since 6.0.0
 */
class DeprecationCollectorProvider extends AbstractCollectorProvider
{
    public function __invoke(Deprecator $deprecator): void
    {
        if ($this->hasCollector(DeprecationCollector::NAME)) {
            return;
        }

        $this->addCollector(new DeprecationCollector($deprecator));
    }
}
