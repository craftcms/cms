<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\Import;

/**
 * @event ImportDispatched The event that is triggered after an import is dispatched to the queue.
 */
class ImportDispatched
{
    /**
     * Carries the dispatched job steps and the import, fired after queue dispatch.
     *
     * @param  array  $steps  The queue job steps to be dispatched.
     */
    public function __construct(
        public array $steps,
        public Import $import,
    ) {}
}
