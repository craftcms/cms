<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use Craft;
use craft\base\Batchable;
use craft\base\ElementInterface;
use craft\db\QueryBatcher;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * Propagates elements to other sites.
 *
 * @since 6.0.0
 */
final class PropagateElements extends BatchedElementJob
{
    /**
     * The site ID(s) that the elements should be propagated to.
     *
     * If null, elements will be propagated to all supported sites, except the one they were queried in.
     *
     * @var int|int[]|null
     */
    public int|array|null $siteId = null;

    /**
     * Creates a new PropagateElements job.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type to propagate.
     * @param  array<string, mixed>|null  $criteria  The element criteria.
     * @param  int|int[]|null  $siteId  The site ID(s) to propagate to.
     * @param  bool  $isNewSite  Whether this is for a newly-added site.
     */
    public function __construct(
        protected string $elementType,
        protected array $criteria = [],
        int|array|null $siteId = null,
        public bool $isNewSite = false,
    ) {
        $this->siteId = $siteId !== null
            ? array_map(fn ($id) => $id, (array) $siteId)
            : null;
    }

    #[\Override]
    protected function loadData(): Batchable
    {
        $query = $this->elementType::find()
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->offset(null)
            ->limit(null)
            ->orderBy(['elements.id' => SORT_ASC]);

        if (! empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    protected function processElement(ElementInterface $element): void
    {
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        $element->newSiteIds = [];
        $element->isNewSite = $this->isNewSite;
        $supportedSiteIds = array_map(fn ($siteInfo) => $siteInfo['siteId'], ElementHelper::supportedSitesForElement($element));
        $elementSiteIds = $this->siteId !== null ? array_intersect($this->siteId, $supportedSiteIds) : $supportedSiteIds;
        $elementsService = Craft::$app->getElements();

        foreach ($elementSiteIds as $siteId) {
            if ($siteId !== $element->siteId) {
                // Make sure the site element wasn't updated more recently than the main one
                $siteElement = $elementsService->getElementById($element->id, $element::class, $siteId);

                if ($siteElement === null || $siteElement->dateUpdated < $element->dateUpdated) {
                    $elementsService->propagateElement($element, $siteId, $siteElement ?? false);
                }
            }
        }

        // It's now fully duplicated and propagated
        $element->markAsDirty();
        $element->afterPropagate(false);
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        $totalItems = $this->totalItems();

        return I18N::prep('Propagating {type}', [
            'type' => $totalItems == 1
                ? $this->elementType::lowerDisplayName()
                : $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
