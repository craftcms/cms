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
use craft\events\ResaveElementEvent;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveElements extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var string|ElementInterface|null The element type that should be resaved
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public $criteria;

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
        $count = $query->count();
        $elementsService = Craft::$app->getElements();

        $callback = function(ResaveElementEvent $e) use ($queue, $query, $count) {
            if ($e->query === $query) {
                $this->setProgress($queue, $e->position / $count);
            }
        };

        $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $callback);
        $elementsService->resaveElements($query);
        $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $callback);
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
        $totalElements = $query->count();
        return Craft::t('app', 'Resaving {type}', [
            'type' => mb_strtolower($totalElements == 1 ? $elementType::displayName() : $elementType::pluralDisplayName()),
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
