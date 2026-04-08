<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Facades\Log;
use Override;

/**
 * Updates element slugs and URIs.
 */
class UpdateElementSlugsAndUris extends Job
{
    private int $totalToProcess = 0;

    private int $totalProcessed = 0;

    /**
     * Creates a new UpdateElementSlugsAndUris job.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type.
     * @param  int|int[]|null  $elementId  The element ID(s) to update.
     * @param  int|null  $siteId  The site ID of the elements.
     * @param  bool  $updateOtherSites  Whether to update other sites.
     * @param  bool  $updateDescendants  Whether to update descendants.
     */
    public function __construct(
        public string $elementType,
        public int|array|null $elementId = null,
        public ?int $siteId = null,
        public bool $updateOtherSites = true,
        public bool $updateDescendants = true,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->totalToProcess = 0;
        $this->totalProcessed = 0;

        $query = $this->createElementQuery()->id($this->elementId);

        $this->processElements($query);
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Updating element slugs and URIs');
    }

    /**
     * Creates an element query for the configured element type.
     */
    private function createElementQuery(): ElementQueryInterface
    {
        return $this->elementType::find()
            ->siteId($this->siteId)
            ->status(null);
    }

    /**
     * Updates the given elements' slugs and URIs.
     */
    private function processElements(ElementQueryInterface $query): void
    {
        $this->totalToProcess += $query->count();

        $query->each(function ($element) {
            // totalToProcess can be 0 somehow (https://github.com/craftcms/cms/issues/16787)
            $this->setProgress((int) (($this->totalProcessed / max($this->totalToProcess, $this->totalProcessed + 1)) * 100));
            $this->totalProcessed++;

            $oldSlug = $element->slug;
            $oldUri = $element->uri;

            try {
                Elements::updateElementSlugAndUri($element, $this->updateOtherSites, false, false);
            } catch (OperationAbortedException $e) {
                Log::info("Couldn't update slug and URI for element $element->id: {$e->getMessage()}");

                return;
            }

            // Only go deeper if something just changed
            if ($this->updateDescendants && ($element->slug !== $oldSlug || $element->uri !== $oldUri)) {
                $childQuery = $this->createElementQuery()
                    ->descendantOf($element)
                    ->descendantDist(1);
                $this->processElements($childQuery);
            }
        }, 100);
    }
}
