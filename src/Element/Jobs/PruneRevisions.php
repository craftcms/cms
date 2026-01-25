<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use Override;

final class PruneRevisions extends Job
{
    /**
     * Creates a new PruneRevisions job.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type.
     * @param  int  $canonicalId  The ID of the canonical element.
     * @param  int  $siteId  The site ID of the source element.
     * @param  int|null  $maxRevisions  The maximum number of revisions allowed.
     */
    public function __construct(
        public string $elementType,
        public int $canonicalId,
        public int $siteId,
        public ?int $maxRevisions = null,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        // Make sure maxRevisions is still set
        if (! $this->maxRevisions && ! Cms::config()->maxRevisions) {
            return;
        }

        $this->maxRevisions ??= Cms::config()->maxRevisions;

        $extraRevisions = $this->elementType::find()
            ->revisionOf($this->canonicalId)
            ->siteId($this->siteId)
            ->status(null)
            ->orderByDesc('num')
            ->offset($this->maxRevisions)
            ->all();

        if (empty($extraRevisions)) {
            return;
        }

        $total = count($extraRevisions);
        $elementsService = Craft::$app->getElements();

        foreach ($extraRevisions as $i => $extraRevision) {
            $this->setProgress((int) ((($i + 1) / $total) * 100));
            $elementsService->deleteElement($extraRevision, true);
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Pruning extra revisions');
    }
}
