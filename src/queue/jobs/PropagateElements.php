<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\events\BatchElementActionEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\elements\db\ElementQueryInterface;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PropagateElements extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var string|ElementInterface The element type that should be propagated
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be propagated
     */
    public $criteria;

    /**
     * @var int|int[]|null The site ID(s) that the elements should be propagated to
     *
     * If this is `null`, then elements will be propagated to all supported sites, except the one they were queried in.
     */
    public $siteId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Let's save ourselves some trouble and just clear all the caches for this element class
        Craft::$app->getTemplateCaches()->deleteCachesByElementType($this->elementType);

        /** @var ElementQuery $query */
        $query = $this->_query();
        $total = $query->count();
        $elementsService = Craft::$app->getElements();

        $callback = function(BatchElementActionEvent $e) use ($queue, $query, $total) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Craft::t('app', '{step} of {total}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $callback);
        $elementsService->propagateElements($query, $this->siteId);
        $elementsService->off(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $callback);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;
        $total = $query->count();
        return Craft::t('app', 'Propagating {type}', [
            'type' => mb_strtolower($total == 1 ? $elementType::displayName() : $elementType::pluralDisplayName()),
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the element query based on the criteria.
     *
     * @return ElementQueryInterface
     */
    private function _query(): ElementQueryInterface
    {
        $query = $this->elementType::find();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        $query
            ->offset(null)
            ->limit(null)
            ->orderBy(null);

        return $query;
    }
}
