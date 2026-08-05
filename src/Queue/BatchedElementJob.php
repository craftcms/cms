<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Base class for large jobs that perform actions on elements.
 *
 * Provides automatic bulk operation management around element processing
 * to optimize performance when modifying many elements.
 */
abstract class BatchedElementJob extends BatchedJob
{
    /**
     * The element type class to query.
     *
     * @var class-string<ElementInterface>
     */
    protected string $elementType;

    /**
     * The criteria to apply to the element query.
     *
     * @var array<string, mixed>
     */
    protected array $criteria = [];

    /**
     * The bulk operation key for the current batch sequence.
     *
     * @internal
     */
    public string $bulkOpKey = '';

    abstract protected function processElement(ElementInterface $element): void;

    protected function getQuery(): Builder
    {
        $query = $this->elementType::find();
        assert($query instanceof ElementQuery);
        $query->orderBy('elements.id');
        $criteria = $this->criteria;
        unset($criteria['offset'], $criteria['limit']);

        if (! empty($criteria)) {
            Typecast::configure($query, $criteria);
        }

        return $query;
    }

    #[\Override]
    protected function queryOffset(): int
    {
        return (int) ($this->criteria['offset'] ?? 0);
    }

    protected function queryLimit(): ?int
    {
        return isset($this->criteria['limit']) ? (int) $this->criteria['limit'] : null;
    }

    protected function processItem(mixed $item): void
    {
        $this->processElement($item);
    }

    protected function before(): void
    {
        $this->bulkOpKey = BulkOps::start();
    }

    protected function beforeBatch(): void
    {
        BulkOps::resume($this->bulkOpKey);
    }

    protected function after(): void
    {
        BulkOps::end($this->bulkOpKey);
    }
}
