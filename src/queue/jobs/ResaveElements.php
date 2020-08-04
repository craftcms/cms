<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\BatchElementActionEvent;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ResaveElements extends BaseJob
{
    /**
     * @var string|ElementInterface|null The element type that should be resaved
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public $criteria;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     * @since 3.4.2
     */
    public $updateSearchIndex = false;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        $total = $query->count();
        if ($query->limit) {
            $total = min($total, $query->limit);
        }
        $elementsService = Craft::$app->getElements();

        $callback = function(BatchElementActionEvent $e) use ($queue, $query, $total) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Craft::t('app', '{step} of {total}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
        $elementsService->resaveElements($query, false, true, $this->updateSearchIndex);
        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;
        return Craft::t('app', 'Resaving {type}', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);
    }

    /**
     * Returns the element query based on the criteria.
     *
     * @return ElementQueryInterface
     */
    private function _query(): ElementQueryInterface
    {
        $elementType = $this->elementType;
        $query = $elementType::find();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return $query;
    }
}
