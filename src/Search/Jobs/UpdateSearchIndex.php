<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Jobs;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Search;
use InvalidArgumentException;
use Override;

class UpdateSearchIndex extends Job
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type.
     * @param  int|int[]|null  $elementId  The element ID(s) to update.
     * @param  int|string|null  $siteId  The site ID or '*' for all sites.
     * @param  string[]|null  $fieldHandles  The field handles to index.
     * @param  bool  $queued  Whether to check if the element's search indexes are queued to be updated.
     */
    public function __construct(
        public string $elementType,
        public int|array|null $elementId = null,
        public int|string|null $siteId = '*',
        public ?array $fieldHandles = null,
        public bool $queued = false,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        if ($this->queued) {
            if (! is_int($this->elementId) || ! is_int($this->siteId)) {
                throw new InvalidArgumentException('`elementId` and `siteId` must be an integer when `queued` is true.');
            }

            Search::indexElementIfQueued($this->elementId, $this->siteId, $this->elementType);

            return;
        }

        $elements = $this->elementType::find()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->id($this->elementId)
            ->siteId($this->siteId)
            ->status(null)
            ->all();

        $total = count($elements);

        foreach ($elements as $i => $element) {
            $this->setProgress((int) ((($i + 1) / max($total, 1)) * 100));
            Search::indexElementAttributes($element, $this->fieldHandles);
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Updating search indexes');
    }
}
