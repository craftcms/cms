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
use craft\queue\BaseBatchedJob;

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.13
 */
class PropagateElements extends BaseBatchedJob
{
    /**
     * @var string The element type that should be propagated
     * @phpstan-var class-string<ElementInterface>
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
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find()
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
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        return Translation::prep('app', 'Propagating {type}', [
            'type' => $this->totalItems() == 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName(),
        ]);
    }
}
