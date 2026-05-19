<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug\Element;

use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use Fruitcake\LaravelDebugbar\CollectorProviders\AbstractCollectorProvider;
use Illuminate\Contracts\Events\Dispatcher;

class ElementCollectorProvider extends AbstractCollectorProvider
{
    public function __invoke(Dispatcher $events): void
    {
        if ($this->hasCollector(ElementCollector::NAME)) {
            return;
        }

        $elementCollector = new ElementCollector;

        $this->addCollector($elementCollector);

        $events->listen(ElementsHydrated::class, fn (ElementsHydrated $event) => $elementCollector->countElements($event->elements, 'retrieved'));
        $events->listen(ElementSaved::class, fn (ElementSaved $event) => $elementCollector->countElement($event->element, $event->isNew ? 'created' : 'updated'));
        $events->listen(ElementDeleted::class, fn (ElementDeleted $event) => $elementCollector->countElement($event->element, 'deleted'));
        $events->listen(ElementRestored::class, fn (ElementRestored $event) => $elementCollector->countElement($event->element, 'restored'));
    }
}
