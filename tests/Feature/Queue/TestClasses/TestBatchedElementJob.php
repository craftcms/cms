<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\Queue\TestClasses;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Queue\BatchedElementJob;

class TestBatchedElementJob extends BatchedElementJob
{
    protected ?string $description = 'Test Element Job';

    /**
     * @var array<int, int>
     */
    public array $processedElementIds = [];

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $criteria
     */
    public function __construct(
        string $elementType,
        array $criteria = [],
    ) {
        parent::__construct();

        $this->elementType = $elementType;
        $this->criteria = $criteria;
    }

    protected function processElement(ElementInterface $element): void
    {
        $this->processedElementIds[] = $element->id;
    }
}
