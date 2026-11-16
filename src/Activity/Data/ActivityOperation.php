<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use Closure;
use CraftCms\Cms\Activity\ActivityEventRecorder;
use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;

/**
 * A larger process that activity events happen as part of, such as an import run.
 *
 * @since 6.0.0
 */
readonly class ActivityOperation
{
    /**
     * @param  class-string<ActivityOperationTypeInterface>  $type
     * @param  array<string, int|string>  $data  Kept with each event, for finding the operation again later.
     */
    public function __construct(
        public string $type,
        public string $id,
        public string $label,
        public array $data = [],
    ) {
        if ($this->id === '' || $this->label === '') {
            throw new InvalidArgumentException('Activity operations require an ID and label.');
        }

        if (! is_a($this->type, ActivityOperationTypeInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('Activity operation types must implement %s.', ActivityOperationTypeInterface::class));
        }

        if (isset($this->data['type']) || isset($this->data['label'])) {
            throw new InvalidArgumentException('Activity operation data can’t use the “type” or “label” keys.');
        }
    }

    /**
     * Runs the callback as part of the operation. Events recorded by queue jobs it dispatches are included.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function scope(Closure $callback): mixed
    {
        return Context::scope($callback, hidden: [ActivityEventRecorder::ContextOperation => $this]);
    }

    /**
     * @return array<string, int|string>
     */
    public function snapshot(): array
    {
        return ['type' => $this->type, 'label' => $this->label, ...$this->data];
    }
}
