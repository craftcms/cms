<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\QueryBatcher;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseBatchedElementJob;

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.13
 */
class PropagateElements extends BaseBatchedElementJob
{
    /**
     * @var class-string<ElementInterface> The element type that should be propagated
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be propagated
     */
    public ?array $criteria = null;

    /**
     * @var int|int[]|null The site ID(s) that the elements should be propagated to
     *
     * If this is `null`, then elements will be propagated to all supported sites, except the one they were queried in.
     */
    public array|int|null $siteId = null;

    /**
     * @var bool Whether this is for a newly-added site.
     * @since 5.6.10
     */
    public bool $isNewSite = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->siteId !== null) {
            $this->siteId = array_map(fn($siteId) => (int)$siteId, (array)$this->siteId);
        }
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        $query = $this->elementType::find()
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->offset(null)
            ->limit(null)
            ->orderBy(['elements.id' => SORT_ASC]);

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        /** @var ElementInterface $item */
        $item->setScenario(Element::SCENARIO_ESSENTIALS);
        $item->newSiteIds = [];
        $item->isNewSite = $this->isNewSite;
        $supportedSiteIds = array_map(fn($siteInfo) => $siteInfo['siteId'], ElementHelper::supportedSitesForElement($item));
        $elementSiteIds = $this->siteId !== null ? array_intersect($this->siteId, $supportedSiteIds) : $supportedSiteIds;
        $elementsService = Craft::$app->getElements();

        foreach ($elementSiteIds as $siteId) {
            if ($siteId !== $item->siteId) {
                // Make sure the site element wasn't updated more recently than the main one
                $siteElement = $elementsService->getElementById($item->id, get_class($item), $siteId);
                if ($siteElement === null || $siteElement->dateUpdated < $item->dateUpdated) {
                    $elementsService->propagateElement($item, $siteId, $siteElement ?? false);
                }
            }
        }

        // It's now fully duplicated and propagated
        $item->markAsDirty();
        $item->afterPropagate(false);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Propagating {type}', [
            'type' => $this->totalItems() == 1
                ? $this->elementType::lowerDisplayName()
                : $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
