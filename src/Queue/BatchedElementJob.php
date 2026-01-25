<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use Craft;
use craft\base\Batchable;
use craft\base\ElementInterface;
use craft\db\QueryBatcher;
use CraftCms\Cms\Element\Queries\ElementQuery;

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

    /**
     * Processes a single element.
     */
    abstract protected function processElement(ElementInterface $element): void;

    protected function loadData(): Batchable
    {
        /** @var ElementQuery $query */
        $query = $this->elementType::find()->orderBy(['elements.id' => SORT_ASC]);

        if (! empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    protected function processItem(mixed $item): void
    {
        $this->processElement($item);
    }

    protected function before(): void
    {
        $this->bulkOpKey = Craft::$app->getElements()->beginBulkOp();
    }

    protected function beforeBatch(): void
    {
        Craft::$app->getElements()->resumeBulkOp($this->bulkOpKey);
    }

    protected function after(): void
    {
        Craft::$app->getElements()->endBulkOp($this->bulkOpKey);
    }
}
